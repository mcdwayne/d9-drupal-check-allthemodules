<?php

namespace Drupal\config_selector;

/**
 * Provides a sort function for sorting config entities for config_selector.
 */
trait ConfigSelectorSortTrait {

  /**
   * Sorts an array of configuration entities by priority then config name.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $configs
   *   Array of configuration entities to sort.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   The sorted array of configuration entities.
   */
  protected function sortConfigEntities(array $configs) {
    uksort($configs, function ($a, $b) use ($configs) {
      $a_priority = $configs[$a]->getThirdPartySetting('config_selector', 'priority', 0);
      $b_priority = $configs[$b]->getThirdPartySetting('config_selector', 'priority', 0);
      if ($a_priority === $b_priority) {
        return strcmp($a, $b);
      }
      return $a_priority < $b_priority ? -1 : 1;
    });
    return $configs;
  }

}
