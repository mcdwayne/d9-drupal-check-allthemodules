<?php
/**
 * @file
 * Wraps your content with a div with bootstrap column size classes.
 */

namespace Drupal\shortcode_asciinema\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for asciinema videos.
 *
 * @Shortcode(
 *   id = "asciinema",
 *   title = @Translation("Asciinema video"),
 *   description = @Translation("Embeds an asciinema video")
 * )
 */
class AsciinemaShortcode extends ShortcodeBase {

  public function process($attributes, $id, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array(
      'autoplay' => '1',
      'loop' => '1',
      'preload' => '1',
      'size' => 'medium',
      'speed' => '1',
      't' => 0,
      'theme' => 'asciinema',
    ),
      $attributes
    );

    $attributes_string = '';
    foreach ($attributes as $key => $value) {
      $attributes_string .= " data-$key=\"$value\"";
    }

    $src = "https://asciinema.org/a/$id.js";

    // Unfortunately, we cannot use a template for this. It causes some weird caching issues, leading to JS crashing
    // the web browser.
    return "<div class=\"shortcode-asciinema-wrapper\"><script type=\"text/javascript\" src=\"$src\" id=\"asciicast-$id\" $attributes_string async></script></div>";
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[asciinema size="medium" autoplay="1" loop="1" theme="asciinema" speed="1" t="0"]105302[/asciinema]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Embeds an asciinema.org video. All attributes are optional. You must insert the video ID as the tag value. E.g., 105302. You can find the ID in the URL of the video:it is the last part of the URL after "https://asciinema.org/a/".') . '</p>';
    }
    else {
      $output[] = $this->t('Embeds an asciinema.org video.') . '</p>';
    }

    return implode(' ', $output);
  }
}
