<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "random",
 *   title = @Translation("Random"),
 *   description = @Translation("Generating random text.")
 * )
 */
class RandomShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'length' => 8,
    ],
      $attributes
    );

    $length = intval($attributes['length']);
    $length = max($length, 8);
    $length = min($length, 99);

    $text = '';
    for ($i = 0; $i < $length; ++$i) {
      $text .= chr(rand(32, 126));
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>[random (length="8") /]</strong>';
    $output[] = $this->t('Inserts a random text with the given length.') . '</p>';
    return implode(' ', $output);
  }

}
