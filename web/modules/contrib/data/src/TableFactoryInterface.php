<?php

namespace Drupal\data;

/**
 * Provides an interface for data handling.
 */
interface TableFactoryInterface {
  /**
   * @param string $name
   *
   * @return Table
   * @throws DataException
   */
  function get($name);
}
