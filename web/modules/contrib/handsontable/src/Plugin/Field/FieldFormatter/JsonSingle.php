<?php

/**
 * @file
 * Contains \Drupal\handsontable\Plugin\Field\FieldFormatter\JsonSingle.
 */

namespace Drupal\handsontable\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementations for 'json_single' formatter.
 *
 * @FieldFormatter(
 *  id = "json_single",
 *  label = @Translation("JSON - single"),
 *  field_types = {"handsontable_single"}
 * )
 */
class JsonSingle extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'pretty_print' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['pretty_print'] = array(
      '#type' => 'checkbox',
      '#title' => t('Pretty print'),
      '#default_value' => $this->getSetting('pretty_print')
    );

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary[] = t('Pretty print: %pretty_print', ['%pretty_print' => $this->getSetting('pretty_print') ? t('yes') : t('no')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = [];
    foreach ($items as $delta => $item) {
      if ($this->getSetting('pretty_print')) {
        $element[$delta]['#prefix'] = '<pre>';
        $element[$delta]['#markup'] = json_encode(json_decode($item->value), JSON_PRETTY_PRINT);
        $element[$delta]['#suffix'] = '</pre>';
      }
      else {
        $element[$delta]['#markup'] = $item->value;
      }

    }
    return $element;

  }

}
