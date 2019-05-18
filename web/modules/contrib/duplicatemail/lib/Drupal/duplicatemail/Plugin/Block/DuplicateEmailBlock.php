<?php

/**
 * @file
 * Contains \Drupal\duplicatemail\Plugin\block\DuplicateEmailBlock.
 */

namespace Drupal\duplicatemail\Plugin\block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block displaying accounts with have duplicate email addresses.
 *
 * @Plugin(
 *   id = "duplicatemail-list",
 *   admin_label = @Translation("Duplicate Mail"),
 *   module = "duplicatemail"
 * )
 */
class DuplicateEmailBlock extends BlockBase {

  /*
   * Builds the duplicate mail block.
   */
  function build() {
    return array(
      '#markup' => duplicatemail_list(),
    );
  }

}
