<?php

namespace Drupal\formfactorykits\Kits\Container\Tabs;

use Drupal\formfactorykits\Kits\FormFactoryKit;

/**
 * Class VerticalTabsKit
 *
 * @package Drupal\formfactorykits\Kits\Container\Tabs
 */
class VerticalTabsKit extends FormFactoryKit {
  const ID = 'vertical_tabs';
  const TYPE = 'vertical_tabs';
  const DEFAULT_TAB_KEY = 'default_tab';
  const IS_CHILDREN_GROUPED = TRUE;

  /**
   * @inheritdoc
   */
  public function getParents() {
    return array_merge(parent::getParents(), [$this->getID()]);
  }

  /**
   * @inheritdoc
   */
  public function getArray() {
    $artifact = [];
    if (!in_array('parents', $this->excludedParameters)) {
      $parents = $this->getParents();
      if (!empty($parents)) {
        $artifact['#parents'] = $parents;
      }
    }
    foreach ($this->parameters as $parameter => $value) {
      if (NULL !== $value) {
        $artifact['#' . $parameter] = $value;
      }
    }
    return $artifact;
  }

  /**
   * @param string $tab
   *
   * @return static
   */
  public function setDefaultTab($tab) {
    return $this->set(self::DEFAULT_TAB_KEY, vsprintf('edit-%s', [
      str_replace('_', '-', $tab),
    ]));
  }

  /**
   * @param array $context
   *
   * @return TabKit
   */
  public function createTab($context = []) {
    $kit = TabKit::create($this->kitsService, $context);
    $this->append($kit);
    return $kit;
  }
}
