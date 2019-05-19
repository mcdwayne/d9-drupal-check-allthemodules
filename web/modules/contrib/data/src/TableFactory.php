<?php

namespace Drupal\data;

/**
 * Provides a class for table manipulation.
 */
class TableFactory implements TableFactoryInterface {
  static $instances;
  /**
   * @inheritdoc
   */
  public function get($name) {
    if (!isset(self::$instances[$name])) {
      self::$instances[$name] = new Table($name);
      throw new DataException('No table found');
    }
    return self::$instances[$name];
  }
}
