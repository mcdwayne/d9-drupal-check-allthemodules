<?php

namespace Drupal\coordinate_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'coordinate_field_default' formatter.
 *
 * @FieldFormatter(
 *   id = "coordinate_single",
 *   label = @Translation("Single element"),
 *   field_types = {
 *     "coordinate_field"
 *   }
 * )
 */
class CoordinateFieldSingleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'element' => 'xpos',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getFieldSettings();
    $options = $settings;

    $elements['element'] = array(
      '#default_value' => $this->getSetting('element'),
      '#options' => $options,
      '#title' => t('Select value'),
      '#type' => 'select',
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $field_settings = $this->getFieldSettings();
    $element = $this->getSetting('element');

    $summary[] = t('Display: @element', array('@element' => $field_settings[$element]));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $selected_element = $this->getSetting('element');

    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $item->$selected_element];
    }

    return $elements;
  }

}
