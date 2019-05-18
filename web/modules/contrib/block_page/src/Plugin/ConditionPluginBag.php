<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\ConditionPluginBag.
 */

namespace Drupal\block_page\Plugin;

use Drupal\Core\Plugin\DefaultPluginBag;

/**
 * Provides a collection of condition plugins.
 */
class ConditionPluginBag extends DefaultPluginBag {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
