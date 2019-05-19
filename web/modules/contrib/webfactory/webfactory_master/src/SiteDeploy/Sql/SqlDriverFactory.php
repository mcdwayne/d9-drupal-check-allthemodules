<?php

namespace Drupal\webfactory_master\SiteDeploy\Sql;

/**
 * SqlDriverFactory build SqlDriverInterface instance according to driver type.
 *
 * @package Drupal\webfactory_master\SiteDeploy\Sql
 */
class SqlDriverFactory {

  /**
   * MySQL driver type.
   */
  const DRIVER_MYSQL = 'mysql';

  /**
   * SqlDriverFactory private constructor.
   */
  private function __construct(){}

  /**
   * Create sql driver according to given type.
   *
   * @param string $driver
   *   Sql driver type, mysql, pgsql for example.
   *
   * @return \Drupal\webfactory_master\SiteDeploy\Sql\SqlDriverInterface
   *   SqlDriverInterface instance.
   */
  public static function getDriver($driver) {
    $driver_class = "Drupal\\webfactory_master\\SiteDeploy\\Sql\\{$driver}\\Driver";
    return new $driver_class();
  }

}
