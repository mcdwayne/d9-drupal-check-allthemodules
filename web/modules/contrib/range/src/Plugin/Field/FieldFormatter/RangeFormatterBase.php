<?php

namespace Drupal\range\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Parent plugin for decimal and integer range formatters.
 */
abstract class RangeFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'range_separator' => '-',
      'thousand_separator' => '',
      'range_combine' => TRUE,
      'from_prefix_suffix' => FALSE,
      'to_prefix_suffix' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['range_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Range separator.'),
      '#default_value' => $this->getSetting('range_separator'),
      '#weight' => 0,
    ];
    $options = [
      '' => $this->t('- None -'),
      '.' => $this->t('Decimal point'),
      ',' => $this->t('Comma'),
      ' ' => $this->t('Space'),
      chr(8201) => $this->t('Thin space'),
      "'" => $this->t('Apostrophe'),
    ];
    $elements['thousand_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $this->getSetting('thousand_separator'),
      '#weight' => 1,
    ];
    $elements['range_combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine equivalent values'),
      '#description' => $this->t('If the FROM and TO values are equal, combine the display into a single value.'),
      '#default_value' => $this->getSetting('range_combine'),
      '#weight' => 10,
    ];
    $elements['from_prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display <em>FROM value</em> prefix and suffix'),
      '#default_value' => $this->getSetting('from_prefix_suffix'),
      '#weight' => 11,
    ];
    $elements['to_prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display <em>TO value</em> prefix and suffix'),
      '#default_value' => $this->getSetting('to_prefix_suffix'),
      '#weight' => 12,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $from_value = $this->numberFormat(1234.1234567890);
    $to_value = $this->numberFormat(4321.0987654321);

    $summary[] = $from_value . $this->getSetting('range_separator') . $to_value;
    if ($this->getSetting('range_combine')) {
      $summary[] = $this->t('Equivalent values will be combined into a single value.');
    }
    if ($this->getSetting('from_prefix_suffix')) {
      $summary[] = $this->t('Display with <em>FROM value</em> prefix and suffix.');
    }
    if ($this->getSetting('to_prefix_suffix')) {
      $summary[] = $this->t('Display with <em>TO value</em> prefix and suffix.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $from_value = $this->numberFormat($item->from);
      $to_value = $this->numberFormat($item->to);

      // Combine values if they are equal.
      if ($this->getSetting('range_combine') && $from_value === $to_value) {
        $output = $this->viewElementCombined($from_value);
      }
      else {
        $output = $this->viewElementSeparate($from_value, $to_value);
      }

      $elements[$delta] = ['#markup' => $output];
    }

    return $elements;
  }

  /**
   * Helper method. Creates the combined value and returns field markup.
   *
   * FROM and TO might have different prefixes/suffixes.
   * Code below decides which one to use, based on the following:
   *   1. If both are disabled - show naked value.
   *   2. If either FROM or TO are enabled - show prefix/suffix of the
   *      enabled one.
   *   3. If both are enabled, show prefix from FROM and suffix from TO.
   *
   * @param string $value
   *   Field value.
   *
   * @return string
   *   Field markup.
   */
  protected function viewElementCombined($value) {
    $from_prefix_suffix = !empty($this->getSetting('from_prefix_suffix'));
    $to_prefix_suffix = !empty($this->getSetting('to_prefix_suffix'));

    // Option #1: both are disabled.
    if (empty($from_prefix_suffix) && empty($to_prefix_suffix)) {
      return $value;
    }
    // Option #2a: FROM is enabled.
    elseif (!empty($from_prefix_suffix) && empty($to_prefix_suffix)) {
      return $this->viewElementPrefixSuffix($value, TRUE, $this->getFieldSetting('from'));
    }
    // Option #2b: TO is enabled.
    elseif (empty($from_prefix_suffix) && !empty($to_prefix_suffix)) {
      return $this->viewElementPrefixSuffix($value, TRUE, $this->getFieldSetting('to'));
    }
    // Option #3: both are enabled.
    else {
      return $this->viewElementPrefixSuffix($value, TRUE, ['prefix' => $this->getFieldSetting('from')['prefix'], 'suffix' => $this->getFieldSetting('to')['suffix']]);
    }
  }

  /**
   * Helper method. Creates and returns field markup for separate values.
   *
   * @param string $from_value
   *   Field FROM value.
   * @param string $to_value
   *   Field TO value.
   *
   * @return string
   *   Field markup.
   */
  protected function viewElementSeparate($from_value, $to_value) {
    $from = $this->viewElementPrefixSuffix($from_value, $this->getSetting('from_prefix_suffix'), $this->getFieldSetting('from'));
    $to = $this->viewElementPrefixSuffix($to_value, $this->getSetting('to_prefix_suffix'), $this->getFieldSetting('to'));

    return $from . $this->getSetting('range_separator') . $to;
  }

  /**
   * Helper method. Adds prefix and suffix to the given range field value.
   *
   * @param string $value
   *   FROM/TO range field value.
   * @param bool $display_prefix_suffix
   *   Whether to add suffix/prefix or not.
   * @param array $settings
   *   Field instance FROM/TO value settings.
   *
   * @return string
   *   Range field value with added prefix/suffix.
   */
  protected function viewElementPrefixSuffix($value, $display_prefix_suffix, array $settings) {
    if ($display_prefix_suffix) {
      $prefix = !empty($settings['prefix']) ? FieldFilteredMarkup::create($settings['prefix']) : '';
      $suffix = !empty($settings['suffix']) ? FieldFilteredMarkup::create($settings['suffix']) : '';
      return $prefix . $value . $suffix;
    }

    return $value;
  }

  /**
   * Formats a number.
   *
   * @param mixed $number
   *   The numeric value.
   *
   * @return string
   *   The formatted number.
   */
  abstract protected function numberFormat($number);

}
