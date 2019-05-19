<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Plugin\Field\FieldWidget\Range.
 */

namespace Drupal\station_schedule\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\station_schedule\DatetimeHelper;

/**
 * @todo.
 *
 * @FieldWidget(
 *   id = "station_schedule_item_range",
 *   label = @Translation("Range (schedule item)"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class Range extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#default_value' => $items[$delta]->value,
      '#increment' => $items->getEntity()->getSchedule()->getIncrement(),
      '#roll_midnight_back' => $this->getFieldSetting('roll_midnight_back'),
      '#process' => [[static::class, 'processRange']],
      '#input' => TRUE,
    ];
    $element['#element_validate'][] = [static::class, 'elementValidate'];
    return $element;
  }

  /**
   * @todo.
   */
  public static function elementValidate(array $element, FormStateInterface $form_state, array $complete_form) {
    $value = $element['value']['#value'];
    $value = ($value['day'] * 60 * 24) + $value['minute'];
    $form_state->setValueForElement($element['value'], $value);
  }

  /**
   * @todo.
   */
  public static function processRange(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $minutes_in_day = 60 * 24;
    $value = ['day' => 0, 'minute' => 0];
    if (!empty($element['#value'])) {
      if (is_array($element['#value'])) {
        $value = $element['#value'];
      }
      else {
        $value = [
          'day' => (int) (($element['#value']) / $minutes_in_day),
          'minute' => $element['#value'] % $minutes_in_day,
        ];
      }
    }

    // Make sure a range that ends on midnight of one day gets pushed back
    // to the previous day.
    if ($element['#roll_midnight_back'] && $value['minute'] == 0) {
      $value['day']--;
      $value['minute'] = $minutes_in_day;
    }

    // Make sure the increment will advance the counter.
    $increment = $element['#increment'];
    if (empty($increment) || $increment < 1) {
      $increment = 1;
    }

    $minute_options = [];
    for ($minute = 0; $minute <= 24 * 60; $minute += $increment) {
      $time = DatetimeHelper::deriveTimeFromMinutes($minute);
      $minute_options[$minute] = $time['time'] . $time['a'];
    }
    $element['#tree'] = TRUE;
    $element['day'] = [
      '#type' => 'select',
      '#default_value' => $value['day'],
      '#options' => DateHelper::weekDays(TRUE),
    ];
    $element['minute'] = [
      '#type' => 'select',
      '#default_value' => $value['minute'],
      '#options' => $minute_options,
    ];

    return $element;
  }

}
