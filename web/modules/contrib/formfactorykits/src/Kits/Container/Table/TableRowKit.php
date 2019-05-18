<?php

namespace Drupal\formfactorykits\Kits\Container\Table;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\kits\Services\KitsInterface;

/**
 * Class TableRowKit
 *
 * @package Drupal\formfactorykits\Kits\Container\Table
 */
class TableRowKit extends FormFactoryKit {
  const ROW_KEY = 'row';

  /**
   * @param array|string $row
   *
   * @return static
   */
  public function setRow($row) {
    return $this->set(self::ROW_KEY, $row);
  }

  /**
   * @inheritdoc
   */
  public function getArray() {
    return $this->get(self::ROW_KEY);
  }
}
