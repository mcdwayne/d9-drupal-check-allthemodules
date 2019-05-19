<?php

namespace Drupal\syncart\Hook;

/**
 * PreprocessPage.
 */
class SyncartPreprocessPage {

  /**
   * Implements hook_preprocess_page().
   */
  public static function hook(&$variables) {
    $block_id = 'small_cart_block';
    $plugin_manager = \Drupal::service('plugin.manager.block');
    $block = $plugin_manager->createInstance($block_id, array());
    if (!empty($block)) {
      $variables['small_cart_block'] = $block->build();
    }
  }

}
