<?php

namespace Drupal\commerce_country_store\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a store selector block.
 *
 * @Block(
 *   id = "commerce_country_store_selector",
 *   admin_label = @Translation("Store Selector"),
 *   category = @Translation("Commerce")
 * )
 */
class StoreSelectorBlock extends BlockBase {

  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\commerce_country_store\Form\StoreSelectorForm');
  }

  public function getCacheContexts() {
    return ['store', 'url'];
  }
}