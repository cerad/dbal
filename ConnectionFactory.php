<?php
namespace Cerad\Component\Dbal;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class ConnectionFactory
{
  static function create($dbUrl)
  {
    $config = new Configuration();
    $connParams = 
    [
      'url' => $dbUrl,
      'driverOptions' => [\PDO::ATTR_EMULATE_PREPARES => false],
    ];
    return DriverManager::getConnection($connParams, $config);
  }
}