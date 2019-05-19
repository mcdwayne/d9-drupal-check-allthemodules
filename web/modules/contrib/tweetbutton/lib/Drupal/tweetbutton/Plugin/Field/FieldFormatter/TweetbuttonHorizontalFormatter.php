<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Plugin\field\formatter\TweetbuttonHorizontalFormatter.
 */

namespace Drupal\tweetbutton\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @FieldFormatter(
 *   id = "tweetbutton_formatter_horizontal",
 *   label = @Translation("Tweetbutton style horizontal"),
 *   field_types = {
 *     "tweetbutton"
 *   }
 * )
 */
class TweetbuttonHorizontalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = array();
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    return $element;
  }
}
