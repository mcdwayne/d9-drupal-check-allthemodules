<?php
/**
 * @file
 * Contains \Drupal\locationmap\Plugin\block\block\LocationMapBlock.
 */

namespace Drupal\locationmap\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block with a static map in it.
 *
 * @Plugin(
 *   id = "location_map_block",
 *   admin_label = @Translation("Location map block"),
 *   module = "locationmap"
 * )
 */
class LocationMapBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    return array(
      'properties' => array(
        // 'administrative' => TRUE
      ),
      'seconds_online' => 900,
      'max_list_count' => 10
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array(
      '#items' => 'Foo',
    );

  }
}