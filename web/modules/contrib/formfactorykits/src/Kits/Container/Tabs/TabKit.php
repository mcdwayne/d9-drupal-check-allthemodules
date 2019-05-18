<?php

namespace Drupal\formfactorykits\Kits\Container\Tabs;

use Drupal\formfactorykits\Kits\Container\DetailsKit;

/**
 * Class TabKit
 *
 * @package Drupal\formfactorykits\Kits\Container\Tabs
 */
class TabKit extends DetailsKit {
  /**
   * @inheritdoc
   */
  public function getArray() {
    $this->excludeParameter(static::PARENTS_KEY);
    return parent::getArray();
  }

  /**
   * @inheritdoc
   */
  public function getChildrenArray() {
    $artifact = [];
    foreach ($this->kits as $kit) {
      /** @var \Drupal\formfactorykits\Kits\FormFactoryKit $kit */
      $kit->excludeParameter($kit::PARENTS_KEY);
      $artifact[$kit->getID()] = $kit->getArray();
    }
    return $artifact;
  }
}
