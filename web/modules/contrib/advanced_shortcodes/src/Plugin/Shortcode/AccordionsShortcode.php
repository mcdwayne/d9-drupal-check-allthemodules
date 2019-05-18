<?php

/**
 * @file
 * Contains \Drupal\advanced_shortcodes\Plugin\Shortcode\AccordionsShortcode.
 */

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * The image shortcode.
 *
 * @Shortcode(
 *   id = "accordions",
 *   title = @Translation("Accordions Container"),
 *   description = @Translation("Accordions container")
 * )
 */
class AccordionsShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // $attributes['class'] = isset($attributes['multiple_active']) && $attributes['multiple_active'] ? 'toggle' : 'accordion';
	$attributes['class'] = isset($attributes['class']) ? $attributes['class'] : '';
	$attributes['class'] .= ' panel-group';
	$output = [
      '#theme' => 'shortcode_accordions',
      '#attributes' => $attributes,
	  '#text'=> $text
    ];
    return $this->render($output);
  }
  
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[accordions (class="additional class")](text)[/accordions]') . '</strong></p> ';
    return implode(' ', $output);
  }

}