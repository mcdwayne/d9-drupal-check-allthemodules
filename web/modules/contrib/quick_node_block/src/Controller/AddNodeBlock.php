<?php

namespace Drupal\quick_node_block\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the block instance add form.
 */
class AddNodeBlock extends ControllerBase {

  /**
   * Build the block instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the block instance.
   * @param string $theme
   *   The name of the theme for the block instance.
   *
   * @return array
   *   The block instance edit form.
   */
  public function blockAddConfigureForm($plugin_id = 'quick_node_block', $theme = '') {

    // Create a block entity.
    $entity = $this->entityTypeManager()->getStorage('block')->create([
      'plugin' => $plugin_id,
      'theme' => $theme,
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
