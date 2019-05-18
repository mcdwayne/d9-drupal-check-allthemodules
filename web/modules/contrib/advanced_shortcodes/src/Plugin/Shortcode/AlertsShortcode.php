<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * @Shortcode(
 *   id = "alerts",
 *   title = @Translation("Bootstrap Alerts"),
 *   description = @Translation("Bootstrap Alerts")
 * )
 */
class AlertsShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    
 	 $output = [
		  '#theme' => 'shortcode_alerts',
		  '#attributes' => $attributes,
		  '#type'=> $attributes['type'],
		  '#message'=>$text
		];
    return $this->render($output);
  }
  
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<p><strong>' . $this->t('[alerts (type="info" class="additional class")](message)[/alerts]') . '</strong></p> ';
    $output[] = '<p><strong> Type :  1-success 2-info 3-warning 4-danger</strong></p> ';
    return implode(' ', $output);
  }

}