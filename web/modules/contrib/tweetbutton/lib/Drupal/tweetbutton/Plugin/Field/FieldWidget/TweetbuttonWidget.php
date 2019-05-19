<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Plugin\Field\FieldWidget\TweetbuttonWidget.
 */

namespace Drupal\tweetbutton\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'tweetbutton' widget.
 *
 * @FieldWidget(
 *   id = "tweetbutton",
 *   label = @Translation("Tweetbutton widget"),
 *   field_types = {
 *     "tweetbutton"
 *   }
 * )
 */
class TweetbuttonWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'display_label' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['display_label'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use field label instead of the "On value" as label'),
      '#default_value' => $this->getSetting('display_label'),
      '#weight' => -1,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element['value'] = $element + array(
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    );
    return $element;
  }

}
