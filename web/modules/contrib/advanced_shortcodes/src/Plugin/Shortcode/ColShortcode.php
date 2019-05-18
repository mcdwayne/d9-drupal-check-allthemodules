<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * @Shortcode(
 *   id = "col",
 *   title = @Translation("Column"),
 *   description = @Translation("Bootstrap Column"),
 * )
 */
class ColShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    $attributes['class'] = isset($attributes['class']) ? $attributes['class'] : '';
    if(isset($attributes['phone'])) {
      $attributes['class'] .= ' col-xs-' . $attributes['phone'];
    }
    if(isset($attributes['tablet'])) {
      $attributes['class'] .= ' col-sm-' . $attributes['tablet'];
    }
    if(isset($attributes['desktop'])) {
      $attributes['class'] .= ' col-md-' . $attributes['desktop'];
    }
    if(isset($attributes['wide'])) {
      $attributes['class'] .= ' col-lg-' . $attributes['wide'];
    }    
   $output = [
      '#theme' => 'shortcode_col',
      '#attributes' => $attributes,
      '#text' => $text
    ];
    return $this->render($output);

  }

	public function tips($long = FALSE) {
		$output = array();
		$output[] = '<p><strong>' . $this->t('[col (class="col-lg-6 col-md-6 col-sm-6 col-xs-6")][/col]') . '</strong></p> ';
		return implode(' ', $output);
	}
}