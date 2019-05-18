<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Plugin\UserBlock\Date.
 */

namespace Drupal\adv_varnish\Plugin\UserBlock;

use Drupal\adv_varnish\UserBlockBase;

/**
 * Provides a language config pages context.
 *
 * @UserBlock(
 *   id = "date",
 *   label = @Translation("Date"),
 * )
 */
class Date extends UserBlockBase {

  public static function content() {
    $user_data = (new \DateTime())->format('Y-m-d H:i:s');
    $selector = '.custom-div';
    return [$selector => $user_data];
  }

}
