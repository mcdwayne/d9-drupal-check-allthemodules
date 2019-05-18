<?php

/**
 * @file
 * Contains \Drupal\openstat\Plugin\Block\OpenstatCounterBlock.
 */

namespace Drupal\openstat\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Openstat counter' block.
 *
 * @Block(
 *   id = "openstat_counter",
 *   admin_label = @Translation("Openstat counter")
 * )
 */
class OpenstatCounterBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {

    $config = \Drupal::config('openstat.settings');
    $type = $config->get('type');

    // Do not show block for invisible counter.
    if (!$type) {
      return NULL;
    }

    $id = $config->get('id');
    $theme = array(
      '#theme' => 'openstat',
      '#id' => $id,
    );
    $theme = drupal_render($theme);

    return array(
      '#type' => 'markup',
      '#markup' => $theme,
    );
  }

}
