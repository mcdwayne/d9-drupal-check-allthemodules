<?php

/**
 * @file
 * Contains \Drupal\block_layout\Entity\BlockLayout.
 */

namespace Drupal\block_layout\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;


/**
 * Defines the BlockLayout entity.
 *
 * The bootstrap blocks entity stores information about 
 * theme suggestions for block.
 *
 * @ConfigEntityType(
 *   id = "block_layout",
 *   label = @Translation("Block Layout"),
 *   module = "block_layout",
 *   config_prefix = "blocks",
 *   admin_permission = "administer site configuration",
 *   handlers = {
 *     "storage" = "Drupal\block_layout\BlockLayoutStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "theme" = "theme",
 *   }
 * )
 */
class BlockLayout extends ConfigEntityBase implements BlockLayoutInterface {

  /**
   * The block id.
   *
   * @var string
   */
  protected $id;

  /**
   * The theme name.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }
}
