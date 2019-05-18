<?php

/**
 * @file
 * Contains \Drupal\google_adwords\Plugin\Field\FieldWidget\GoogleAdwordsText.
 */

namespace Drupal\google_adwords\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'google_adwords_text' widget.
 *
 * @FieldWidget(
 *   id = "google_adwords_text",
 *   label = @Translation("Google AdWords text"),
 *   field_types = {
 *     "google_adwords_tracking"
 *   }
 * )
 */
class GoogleAdwordsText extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => 'Google AdWords tracking words',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    $element['words'] = $element + array(
      '#type' => 'textfield',
      '#title' => $this->getSetting('placeholder'),
      '#default_value' => isset($items[$delta]->words) ? $items[$delta]->words : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    );

    return $element;
  }

}
