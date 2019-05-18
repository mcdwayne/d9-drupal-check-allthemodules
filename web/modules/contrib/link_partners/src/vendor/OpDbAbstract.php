<?php

namespace Drupal\link_partners\vendor;

/**
 * Returns a new class object.
 */
class OpDbAbstract {

  private static $_tables = [];

  /**
   * @return Op_Db_Table_Abstract
   */
  public static function getInstance($options) {
    $class = get_called_class();
    if (empty(self::$_tables[$class])) {
      self::$_tables[$class] = new $class($options);
    }

    return self::$_tables[$class];
  }
}