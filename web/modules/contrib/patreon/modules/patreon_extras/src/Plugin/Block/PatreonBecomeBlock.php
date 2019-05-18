<?php

namespace Drupal\patreon_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PatreonBecomeBlock' block.
 *
 * @Block(
 *  id = "patreon_become_block",
 *  admin_label = @Translation("Patreon Become a Patron block"),
 * )
 */
class PatreonBecomeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('patreon.settings');
    $build = [];

    if ($id = $config->get('patreon_creator_id')) {
      $build['patreon_become_block']['#markup'] = '<a href="https://www.patreon.com/bePatron?u=' . $id . '" data-patreon-widget-type="become-patron-button">Become a patron</a><script async src="https://c6.patreon.com/becomePatronButton.bundle.js"></script>';
    }

    return $build;
  }

}
