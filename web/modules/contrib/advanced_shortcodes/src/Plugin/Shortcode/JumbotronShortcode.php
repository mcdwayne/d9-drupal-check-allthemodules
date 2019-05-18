<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * @Shortcode(
 *   id = "jumbotron",
 *   title = @Translation("Bootstrap Jumbotron"),
 *   description = @Translation("Bootstrap Jumbotron")
 * )
 */
class JumbotronShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    
 	 $output = [
		  '#theme' => 'shortcode_jumbotron',
		  '#attributes' => $attributes,
		  '#text'=> $text,
		  '#title'=>$attributes['title'] 
		];
    return $this->render($output);
  }
  
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[jumbotron (title="jumbotron title" class="additional class")](text)[/jumbotron]') . '</strong></p> ';
    return implode(' ', $output);
  }
}