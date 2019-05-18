<?php

namespace Drupal\advanced_shortcodes\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * The image shortcode.
 *
 * @Shortcode(
 *   id = "row",
 *   title = @Translation("Row for columns"),
 *   description = @Translation("Row bootstrap tag"),
 * )
 */
class RowShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attrs, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    $attrs['class'] = 'row';
    $output = [
      '#theme' => 'shortcode_row',
      '#attributes' => $attrs,
      '#text' => $text
    ];
    return $this->render($output);


  }

	public function tips($long = FALSE) {
		$output = array();
		$output[] = '<p><strong>' . $this->t('[row (class="class here")][/row]') . '</strong></p> ';
		return implode(' ', $output);
	}
}