<?php
namespace Cerad\Component\Dbal;

use Doctrine\DBAL\Connection;

class Repository
{
  protected $dbConn;
  
  protected $c2p = [];
  protected $p2c = [];
  
  protected $joins = [];
  
  protected $tableSelects    = [];
  protected $tableDefaults   = [];
  protected $tablePrimaryKey = [];
  
  public function __construct(Connection $dbConn)
  {
    $this->dbConn = $dbConn;    
  }
  protected function getTableAlias($tableName)
  {
    return substr($tableName,0,strlen($tableName)-1);
  }
  protected function loadTable($tableName,$tableAliasArg = null)
  {
    // Strip trailing s if no alias provided
    $tableAlias = ($tableAliasArg !== null) ? $tableAliasArg : $this->getTableAlias($tableName);
    
    $sm = $this->dbConn->getSchemaManager();
    
    $table = $sm->listTableDetails($tableName);
    
    $primaryKeyCols = $table->getPrimaryKey()->getColumns();
    $primaryKey = implode(',',$primaryKeyCols);
    $this->tablePrimaryKey[$tableName] = $primaryKey;
    
    $cols = $table->getColumns();
    
    $selects  = [];
    $defaults = [];
    foreach($cols as $colName => $col)
    {
      $colNameParts = explode('_',$colName);
      for($i = 1, $ii = count($colNameParts); $i < $ii; $i++)
      {
        $colNameParts[$i] = ucfirst($colNameParts[$i]);
      }
      $propName = implode(null,$colNameParts);
      
      if (count($colNameParts)) 
      {
        $this->c2p[ $colName] = $propName;
        $this->p2c[$propName] =  $colName;
      }
      $selects[] = sprintf('%s.%s AS %s__%s',$tableAlias,$colName,$tableAlias,$propName);
      
      $defaults[$colName] = $col->getDefault();
    }
    $this->tableSelects[$tableName . '.' . $tableAlias] = $selects;
    
    $this->tableDefaults[$tableName] = $defaults;
  }
  /* ===============================================
   * Returns all the columns of a table with aliases
   * auth.user_id AS auth__userId
   */
  protected function getTableSelects($tableName,$tableAlias = null)
  {
    $key = $tableName . '.' . $tableAlias;
    
    if (!isset($this->tableSelects[$key])) 
    {
      $this->loadTable($tableName,$tableAlias);
    }
    return $this->tableSelects[$key];
  }
  /* =============================================
   * This returns all the columns in the table
   * Columns with a default value will have the value
   * The rest are just null
   */
  protected function getTableDefaults($tableName)
  {
    if (!isset($this->tableDefaults[$tableName]))
    {
      $this->loadTable($tableName);
    }
    return $this->tableDefaults[$tableName];
  }
  protected function getTablePrimaryKey($tableName)
  {
    if (!isset($this->tablePrimaryKey[$tableName]))
    {
      $this->loadTable($tableName);
    }
    return $this->tablePrimaryKey[$tableName];
  }
  protected function extractItem($prefix,$row)
  {
    $item = [];
    $prefixLen = strlen($prefix);
    foreach($row as $key => $value)
    {
      if (substr($key,0,$prefixLen) === $prefix)
      {
        $item[substr($key,$prefixLen)] = $value;
      }
    }
    return $item;
  }
  protected function insertItem($tableName,$itemProps)
  {
    $itemDefaults = $this->getTableDefaults($tableName);

    $primaryKey = $this->getTablePrimaryKey($tableName);

    unset($itemDefaults[$primaryKey]);

    $c2p = $this->c2p;
    $item = [];
    foreach (array_keys($itemDefaults) as $colName) {
      $propName = isset($c2p[$colName]) ? $c2p[$colName] : $colName;

      if (array_key_exists($propName, $itemProps)) {
        $item[$colName] = $itemProps[$propName];
      }
    }
    $this->dbConn->insert($tableName, $item);

    return $this->dbConn->lastInsertId();
  }
  /* =============================
   * The gory details of loading on one item
   * And transforming the column names
   */
  protected function findItem($tableName,$itemId)
  {
    $tableAlias = $this->getTableAlias($tableName);

    $qb = $this->dbConn->createQueryBuilder();
    $qb->select($this->getTableSelects($tableName,$tableAlias));
    $qb->from($tableName,$tableAlias);

    $primaryKeyName = $this->getTablePrimaryKey($tableName);

    $qb->andWhere($primaryKeyName . ' = :' . $primaryKeyName);

    $qb->setParameter($primaryKeyName,$itemId);

    $rows = $qb->execute()->fetchAll();

    return count($rows) === 1 ? $this->extractItem($tableAlias . '__',$rows[0]) : null;
  }
  /* =============================
   * The gory details of finding multiple items
   * TODO: Add generic criteria?
   */
  protected function findItems($tableName)
  {
    $tableAlias = $this->getTableAlias($tableName);

    $qb = $this->dbConn->createQueryBuilder();
    $qb->select($this->getTableSelects($tableName,$tableAlias));
    $qb->from($tableName,$tableAlias);

    $stmt  = $qb->execute();
    $items = [];
    while ($row = $stmt->fetch()) {
      $items[] = $this->extractItem($tableAlias . '__',$row);
    }
    return $items;
  }
}