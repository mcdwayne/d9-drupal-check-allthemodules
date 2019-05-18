<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * @Shortcode(
 *   id = "hr",
 *   title = @Translation("HR Border Line"),
 *   description = @Translation("Hr Tag"),
 * )
 */
class HrShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
   
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
	  $output = [
      '#theme' => 'shortcode_hr',
      '#attributes' => $attributes
    ];
    return $this->render($output);

  }
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[hr (class="additional class")][/hr]') . '</strong></p> ';
    return implode(' ', $output);
  }

}