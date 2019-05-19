<?php

namespace Drupal\time_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'time_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "number_time",
 *   label = @Translation("Time"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class TimeFieldFormatter extends FormatterBase {

  /**
   * Denotes that the field value should be treated as number of seconds.
   */
  const STORAGE_SECONDS = 0;

  /**
   * Denotes that the field value should be treated as number of milliseconds.
   */
  const STORAGE_MILLISECONDS = 1;

  /**
   * Denotes that the field should be displayed as "123h 59m 59s 999ms".
   */
  const DISPLAY_HMSMS = 0;

  /**
   * Denotes that the field should be displayed as "123h 59m 59s".
   */
  const DISPLAY_HMS = 1;

  /**
   * Denotes that the field should be displayed as "123:59:59.999".
   */
  const DISPLAY_NUMBERSMS = 2;

  /**
   * Denotes that the field should be displayed as "123:59:59".
   */
  const DISPLAY_NUMBERS = 3;

  /**
   * Denotes that the Hours component should always be displayed.
   */
  const HOURS_ALWAYS = 0;

  /**
   * Denotes that the Hours component should be displayed only if hours > 0.
   */
  const HOURS_OPTIONAL = 1;

  /**
   * Denotes that the Hours component should never be displayed.
   */
  const HOURS_NEVER = 2;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'storage' => self::STORAGE_MILLISECONDS,
      'display' => self::DISPLAY_NUMBERSMS,
      'hours' => self::HOURS_ALWAYS,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'storage' => [
        '#type' => 'select',
        '#title' => $this->t('Storage'),
        '#options' => [
          self::STORAGE_SECONDS => $this->t('Seconds'),
          self::STORAGE_MILLISECONDS => $this->t('Milliseconds'),
        ],
        '#default_value' => $this->getSetting('storage'),
      ],
      'display' => [
        '#type' => 'select',
        '#title' => $this->t('Display'),
        '#options' => [
          self::DISPLAY_HMSMS => $this->t('123h 59m 59s 999ms'),
          self::DISPLAY_HMS => $this->t('123h 59m 59s'),
          self::DISPLAY_NUMBERSMS => $this->t('123:59:59.999'),
          self::DISPLAY_NUMBERS => $this->t('123:59:59'),
        ],
        '#default_value' => $this->getSetting('display'),
      ],
      'hours' => [
        '#type' => 'select',
        '#title' => $this->t('Display hours'),
        '#options' => [
          self::HOURS_ALWAYS => $this->t('Always'),
          self::HOURS_OPTIONAL => $this->t('Optional'),
          self::HOURS_NEVER => $this->t('Never'),
        ],
        '#default_value' => $this->getSetting('hours'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    switch ($this->getSetting('storage')) {
      case self::STORAGE_SECONDS:
        $summary['storage'] = $this->t('Storage: Seconds');
        break;

      case self::STORAGE_MILLISECONDS:
        $summary['storage'] = $this->t('Storage: Milliseconds');
        break;
    }

    switch ($this->getSetting('display')) {
      case self::DISPLAY_HMSMS:
        $summary['display'] = $this->t('Display: 123h 59m 59s 999ms');
        break;

      case self::DISPLAY_HMS:
        $summary['display'] = $this->t('Display: 123h 59m 59s');
        break;

      case self::DISPLAY_NUMBERSMS:
        $summary['display'] = $this->t('Display: 123:59:59.999');
        break;

      case self::DISPLAY_NUMBERS:
        $summary['display'] = $this->t('Display: 123:59:59');
        break;
    }

    switch ($this->getSetting('hours')) {
      case self::HOURS_ALWAYS:
        $summary['hours'] = $this->t('Display hours: Always');
        break;

      case self::HOURS_OPTIONAL:
        $summary['hours'] = $this->t('Display hours: Optional');
        break;

      case self::HOURS_NEVER:
        $summary['hours'] = $this->t('Display hours: Never');
        break;
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $value = $item->value;
    if ($this->getSetting('storage') == self::STORAGE_SECONDS) {
      $value *= 1000;
    }
    $value = round($value);

    $milliseconds = $value % 1000;
    $value = ($value - $milliseconds) / 1000;
    $seconds = $value % 60;
    $value = ($value - $seconds) / 60;

    if ($this->getSetting('hours') == self::HOURS_NEVER) {
      $minutes = $value;
      $value = 0;
    }
    else {
      $minutes = $value % 60;
      $value = ($value - $minutes) / 60;
    }

    $include_hours = $value || $this->getSetting('hours') == self::HOURS_ALWAYS;

    $return = 'N/A';
    switch ($this->getSetting('display')) {
      case self::DISPLAY_HMSMS:
        $return = $include_hours ? "{$value}h " : '';
        $return .= "{$minutes}m {$seconds}s {$milliseconds}ms";
        break;

      case self::DISPLAY_HMS:
        $return = $include_hours ? "{$value}h " : '';
        $return .= "{$minutes}m {$seconds}s";
        break;

      case self::DISPLAY_NUMBERSMS:
        $format = $include_hours ? "{$value}:%02s:%02s.%03s" : '%s:%02s.%03s';
        $return = sprintf($format, $minutes, $seconds, $milliseconds);
        break;

      case self::DISPLAY_NUMBERS:
        $format = $include_hours ? "{$value}:%02s:%02s" : '%s:%02s';
        $return = sprintf($format, $minutes, $seconds, $milliseconds);
        break;
    }

    return $return;
  }

}
