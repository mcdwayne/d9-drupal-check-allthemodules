<?php

namespace Drupal\gitlab_time_tracker\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'gitlab_time_tracker_time_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "gitlab_time_tracker_time_field_formatter",
 *   label = @Translation("Time tracker import time field formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class TimeTrackerImportTimeFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'use_suffix' => TRUE,
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['use_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use suffix for hour formatting.'),
      '#default_value' => $this->getSetting('use_suffix'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

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
    $output = (round($item->value / 60, 2));
    if ($this->getSetting('use_suffix')) {
      $output .= 'h';
    }

    return $output;
  }

}
