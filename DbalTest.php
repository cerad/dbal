<?php

namespace Cerad\Component\Dbal;

class DbalTest extends \PHPUnit_Framework_TestCase
{
  protected $dbUrl = 'mysql://tests:tests@localhost/tests';

  static function setUpBeforeClass()
  {
    $cmd = 'mysql --login-path=tests < schema.sql';
    system($cmd);
  }
  public function testConnectionFactory()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $this->assertEquals('Doctrine\DBAL\Connection',get_class($dbConn));
    $this->assertTrue($dbConn->connect());
  }
  /** @noinspection PhpUndefinedNamespaceInspection */
  /** @noinspection PhpUndefinedClassInspection */
  /**
   * @expectedException Doctrine\DBAL\DBALException
   */
  public function testConnectionFactoryFail()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl . 'x');
    $dbConn->connect();
  }
  public function testRepositoryGetTableAlias()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('getTableAlias');
    $repoMethod->setAccessible(true);

    $tableAlias = $repoMethod->invoke($repo,'users');
    $this->assertEquals('user',$tableAlias);
  }
  public function testRepositoryGetPrimaryKey()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('getTablePrimaryKey');
    $repoMethod->setAccessible(true);

    $primaryKeyName = $repoMethod->invoke($repo,'users');
    $this->assertEquals('id',$primaryKeyName);
  }
  public function testRepositoryGetTableDefaults()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('getTableDefaults');
    $repoMethod->setAccessible(true);

    $defaults = $repoMethod->invoke($repo,'users');
    $this->assertEquals('Active',$defaults['status']);
  }
  public function testRepositoryGetTableSelects()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('getTableSelects');
    $repoMethod->setAccessible(true);

    $selects = $repoMethod->invoke($repo,'user_auths','auth');
    $this->assertEquals('auth.user_id AS auth__userId',$selects[1]);
  }
  public function testRepositoryExtractItem()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('extractItem');
    $repoMethod->setAccessible(true);

    $row = [
      'user__id'            => 1,
      'user__userName'      => 'Art',
      'user_auth__id'       => 2,
      'user_auth__userId'   => 1,
      'user_auth__provider' => 'google',
    ];
    $user = $repoMethod->invoke($repo,'user__',$row);
    $this->assertEquals(2,count($user));

    $auth = $repoMethod->invoke($repo,'user_auth__',$row);
    $this->assertEquals(3,count($auth));
    $this->assertEquals(1,$auth['userId']);
  }
  public function testRepositoryInsertItem()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('insertItem');
    $repoMethod->setAccessible(true);

    $user = [
      'userName' => 'ahundiak',
      'dispName' => 'Art Hundiak',
    ];
    $userId = $repoMethod->invoke($repo,'users',$user);
    $this->assertTrue($userId > 0);

    return $userId;
  }
  /**
   * @depends testRepositoryInsertItem
   */
  public function testRepositoryFindItem($userId)
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('findItem');
    $repoMethod->setAccessible(true);

    $user = $repoMethod->invoke($repo,'users',$userId);

    $this->assertEquals('Art Hundiak',$user['dispName']);
  }
  /**
   * @depends testRepositoryInsertItem
   */
  public function testRepositoryFindItems()
  {
    $dbConn = ConnectionFactory::create($this->dbUrl);
    $repo = new Repository($dbConn);

    $repoClass = new \ReflectionClass($repo);
    $repoMethod = $repoClass->getMethod('findItems');
    $repoMethod->setAccessible(true);

    $users = $repoMethod->invoke($repo,'users');

    $this->assertEquals(1,count($users));
    $this->assertEquals('ahundiak',$users[0]['userName']);
  }
}