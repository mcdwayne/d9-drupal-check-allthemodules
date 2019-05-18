<?php

namespace Drupal\hangouts;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Class HangoutsImages contains utility procedures.
 *
 * @package Drupal\hangouts
 */
class HangoutsUtils {

  /**
   * Renders new Hangouts icon.
   *
   * @param string $size
   *   Hangouts button size.
   *
   * @return Drupal\Component\Render\FormattableMarkup
   *   HTML code contains image button with appropriate size.
   */
  public static function getHangoutsImage($size) {
    $config = \Drupal::config('hangouts.settings');
    $url_prefix = $config->get('icon_src_prefix');
    $url_suffix = $config->get('icon_src_suffix');
    return new FormattableMarkup('<img src="@prefix@size@suffix" alt="Hangouts icon @size">',
      [
        '@size' => $size,
        '@prefix' => $url_prefix,
        '@suffix' => $url_suffix,
      ]
    );
  }

  /**
   * Generates array keyed with icon size and valued with image icon.
   *
   * @return array
   *   Described above.
   */
  public static function getHangoutsImages() {
    $button_sizes = \Drupal::config('hangouts.settings')->get('icon_avail_sizes');
    $button_sizes = array_fill_keys($button_sizes, '');
    foreach ($button_sizes as $key => &$val) {
      $val = self::getHangoutsImage($key);
    }
    return $button_sizes;
  }

}
