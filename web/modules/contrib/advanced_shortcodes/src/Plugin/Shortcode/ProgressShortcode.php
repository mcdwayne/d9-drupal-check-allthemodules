<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * @Shortcode(
 *   id = "progress",
 *   title = @Translation("Progress Bar"),
 *   description = @Translation("Progress Bar line"),
 * )
 */
class ProgressShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    
    $percent = isset($attributes['percent']) && $attributes['percent'] ? $attributes['percent'] : 0;
	 $output = [
		  '#theme' => 'shortcode_progress',
		  '#attributes' => $attributes,
		  '#value'=> $percent 
		];
    return $this->render($output);
  }
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[progress (percent="50" class="additional class")][/progress]') . '</strong></p> ';
    return implode(' ', $output);
  }
}