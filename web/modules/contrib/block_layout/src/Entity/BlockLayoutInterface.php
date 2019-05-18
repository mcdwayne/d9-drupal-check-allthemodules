<?php

/**
 * @file
 * Contains \Drupal\block_layout\Entity\BlockLayoutInterface.
 */

namespace Drupal\block_layout\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for BlockLayout.
 */
interface BlockLayoutInterface extends ConfigEntityInterface {

  public function getTheme();
}
