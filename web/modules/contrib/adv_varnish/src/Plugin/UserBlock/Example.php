<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Plugin\VarnishCacheableEntity\Page.
 */

namespace Drupal\adv_varnish\Plugin\UserBlock;

use Drupal\adv_varnish\UserBlockBase;

/**
 * Provides a language config pages context.
 *
 * @UserBlock(
 *   id = "example",
 *   label = @Translation("Example"),
 * )
 */
class Example extends UserBlockBase {

  public static function content() {
    return ['#div_id' => 'Example'];
  }

}
