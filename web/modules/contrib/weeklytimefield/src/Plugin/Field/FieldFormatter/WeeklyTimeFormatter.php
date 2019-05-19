<?php

namespace Drupal\weeklytime\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\weeklytime\Plugin\Field\FieldType\WeeklyTimeField;

/**
 * Plugin implementation of the 'weeklytime_widget' formatter.
 *
 * @FieldFormatter(
 *   id = "weeklytime_widget",
 *   label = @Translation("Weekly time widget"),
 *   field_types = {
 *     "weeklytime"
 *   }
 * )
 */
class WeeklyTimeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // @todo: Implement settings summary.
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
    // Create human readable time.
    $time = WeeklyTimeField::formatTime($item->time);

    // Create human readable days.
    $days = [];
    if ($item->sun && $item->mon && $item->tue && $item->wed && $item->thu && $item->fri && $item->sat) {
      $days[] = 'every day';
    }
    elseif (!$item->sun && $item->mon && $item->tue && $item->wed && $item->thu && $item->fri && !$item->sat) {
      $days[] = 'week days';
    }
    elseif ($item->sun && !$item->mon && !$item->tue && !$item->wed && !$item->thu && !$item->fri && $item->sat) {
      $days[] = 'weekend days';
    }
    else {
      foreach (WeeklyTimeField::dayOptions() as $day => $text) {
        if ($item->{$day}) {
          $days[] = $text;
        }
      }
    }

    return $this->t('%time %days', [
      '%time' => $time,
      '%days' => implode(', ', $days),
    ]);
  }

}
