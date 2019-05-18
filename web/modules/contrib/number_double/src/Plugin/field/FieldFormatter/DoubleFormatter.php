<?php

namespace Drupal\number_double\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'number_double' formatter.
 *
 * @FieldFormatter(
 *   id = "number_double",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "double"
 *   }
 * )
 */
class DoubleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'decimal_separator' => '.',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = array();

    $elements['decimal_separator'] = array(
      '#type' => 'select',
      '#title' => t('Decimal marker'),
      '#options' => array('.' => t('Decimal point'), ',' => t('Comma')),
      '#default_value' => $this->getSetting('decimal_separator'),
      '#weight' => 5,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->valueFormat(1234.1234567890, $this->getSetting('decimal_separator'));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $output = $this->valueFormat($item->value, $this->getSetting('decimal_separator'));

      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->value != $output) {
        $item->_attributes += array('content' => $item->value);
      }

      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

  /**
   * Formats a number.
   *
   * @param mixed $number
   *   The numeric value.
   * @param string $separator
   *   The string to use as the decimal separator.
   *
   * @return string
   *   The formatted number.
   */
  protected function valueFormat($number, $separator) {
    return strtr($number, '.', $separator);
  }

}
