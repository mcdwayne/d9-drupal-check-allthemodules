<?php

/**
 * @file
 * Contains \Drupal\advanced_shortcodes\Plugin\Shortcode\AccordionShortcode.
 */

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * The image shortcode.
 *
 * @Shortcode(
 *   id = "accordion",
 *   title = @Translation("Accordion Item"),
 *   description = @Translation("Accordion Item"),

 * )
 */
class AccordionShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    $icon = isset($attributes['icon']) && $attributes['icon']  ? '<i class="' . $attributes['icon'] . '"></i> ' : '';
	$attributes['class'] = isset($attributes['class']) ? $attributes['class'] : '';
	$attributes['class'] .=" panel panel-default";
	$id=uniqid();
	 $output = [
      '#theme' => 'shortcode_accordion',
      '#attributes' => $attributes,
	  '#text'=> $text ,
	  '#id'=>  $id ,
	  '#icon'=>  $icon ,
	  '#title'=> $attributes['title']
    ];
    return $this->render($output);


  }
	public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[accordion  (title="Accordion title" icon="class icon name" class="additional class")](text)[/accordion]') . '</strong></p> ';
    return implode(' ', $output);
  }

}