<?php

/**
 * @file
 * Contains \Drupal\content_callback_block\Plugin\Derivative\ContentCallbackBlock.
 */

namespace Drupal\content_callback_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Retrieves content callbacks for each view.
 */
class ContentCallbackBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $manager = \Drupal::service('plugin.manager.content_callback');

    foreach ($manager->getDefinitions() as $id => $definition) {
      if (!isset($definition['deriver'])) {
        $this->derivatives[$id] = $base_plugin_definition;
        $this->derivatives[$id]['admin_label'] = $definition['title'];
        $this->derivatives[$id]['category'] = t('Callbacks');
      }
    }

    return $this->derivatives;
  }
}
