<?php

namespace Drupal\availability_timing\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DateHelper;

/**
 * Plugin implementation of the 'tablefield' widget.
 *
 * @FieldWidget (
 *   id = "availability_timing_default",
 *   label = @Translation("Availability timing default"),
 *   field_types = {
 *     "availability_timing"
 *   },
 * )
 */
class AvailabilityTimingWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $date_helper = new DateHelper();

    $element['#element_validate'] = [
      [$this, 'validate'],
    ];

    $element['period'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline']],
    ];

    $element['period']['start_period'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Start period'),
      '#attributes' => ['class' => ['form--inline']],
    ];

    $element['period']['start_period']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => ['' => ''] + array_combine(range(1, 12), range(1, 12)),
      '#default_value' => isset($item->start_period_month) ? $item->start_period_month : NULL,
    ];

    $element['period']['start_period']['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => ['' => ''] + array_combine(range(1, 31), range(1, 31)),
      '#default_value' => isset($item->start_period_day) ? $item->start_period_day : NULL,
    ];

    $element['period']['end_period'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('End period'),
      '#attributes' => ['class' => ['form--inline']],
    ];

    $element['period']['end_period']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => ['' => ''] + array_combine(range(1, 12), range(1, 12)),
      '#default_value' => isset($item->end_period_month) ? $item->end_period_month : NULL,
    ];

    $element['period']['end_period']['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => ['' => ''] + array_combine(range(1, 31), range(1, 31)),
      '#default_value' => isset($item->end_period_day) ? $item->end_period_day : NULL,
    ];

    $element['timing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Timing'),
    ];

    $week_days = $date_helper->weekDays(TRUE);
    $week_days = $date_helper->weekDaysOrdered($week_days);

    $days_abbr = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    $days_abbr = $date_helper->weekDaysOrdered($days_abbr);

    $days = [];
    foreach ($days_abbr as $days_abbr_key => $days_abbr_value) {
      $days[strtolower($days_abbr_value)] = $week_days[$days_abbr_key];
    }

    $timing = isset($item->timing) ? $item->timing : [];

    $minute_options = ['' => ''] + array_combine(range(0, 59, $this->getFieldSetting('minute_granularity')), range(0, 59, $this->getFieldSetting('minute_granularity')));
    foreach ($days as $day_key => $day_name) {
      $element['timing'][$day_key] = [
        '#type' => 'container',
      ];
      $element['timing'][$day_key]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $day_name,
        '#default_value' => isset($item->{$day_key}) ? $item->{$day_key} : 0,
      ];
      $baseName = $this->fieldDefinition->getName() . '_' . $delta . '_' . $day_key;
      $scheduleId = str_replace('_', '-', $baseName) . '-schedule';
      $element['timing'][$day_key]['schedule'] = [
        '#type' => 'container',
        '#prefix' => '<div id="' . $scheduleId . '">',
        '#suffix' => '</div>',
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getCheckBaseNameByElement($element) . '[' . $delta . '][timing][' . $day_key . '][enable]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $deltas = $form_state->get($baseName . '_deltas') ? $form_state->get($baseName . '_deltas') : 0;
      if (empty($deltas) && !empty($timing[$day_key])) {
        $deltas = count($timing[$day_key]);
        $form_state->set($baseName . '_deltas', $deltas);
      }
      for ($i = 0; $i < $deltas; $i++) {
        $element['timing'][$day_key]['schedule'][$i] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['form--inline']],
        ];

        $element['timing'][$day_key]['schedule'][$i]['start_time_hour'] = [
          '#type' => 'select',
          '#title' => $this->t('Hour'),
          '#options' => $date_helper->hours('G', FALSE),
          '#default_value' => isset($timing[$day_key][$i]['start_time_hour']) ? $timing[$day_key][$i]['start_time_hour'] : NULL,
        ];
        $element['timing'][$day_key]['schedule'][$i]['start_time_minute'] = [
          '#type' => 'select',
          '#title' => $this->t('Minute'),
          '#options' => $minute_options,
          '#default_value' => isset($timing[$day_key][$i]['start_time_minute']) ? $timing[$day_key][$i]['start_time_minute'] : NULL,
        ];
        $element['timing'][$day_key]['schedule'][$i]['separator'] = [
          '#markup' => '<span class="form-item hour-separator"> ' . $this->t('until') . ' </span>',
        ];
        $element['timing'][$day_key]['schedule'][$i]['end_time_hour'] = [
          '#type' => 'select',
          '#title' => $this->t('Hour'),
          '#options' => $date_helper->hours('G', FALSE),
          '#default_value' => isset($timing[$day_key][$i]['end_time_hour']) ? $timing[$day_key][$i]['end_time_hour'] : NULL,
        ];
        $element['timing'][$day_key]['schedule'][$i]['end_time_minute'] = [
          '#type' => 'select',
          '#title' => $this->t('Minute'),
          '#options' => $minute_options,
          '#default_value' => isset($timing[$day_key][$i]['end_time_minute']) ? $timing[$day_key][$i]['end_time_minute'] : NULL,
        ];

      }

      $addMoreName = str_replace('_', '-', $baseName) . '-addmore';
      $element['timing'][$day_key]['schedule']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => [[get_class($this), 'scheduleNewItem']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'scheduleNewItemAjax'],
          'wrapper' => $scheduleId,
        ],
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getCheckBaseNameByElement($element) . '[' . $delta . '][timing][' . $day_key . '][enable]"]' => ['checked' => TRUE],
          ],
        ],
        '#name' => $addMoreName,
      ];

      $removeItemName = str_replace('_', '-', $baseName) . '-removeitem';
      $element['timing'][$day_key]['schedule']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [[get_class($this), 'scheduleRemoveItem']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'scheduleRemoveItemAjax'],
          'wrapper' => $scheduleId,
        ],
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getCheckBaseNameByElement($element) . '[' . $delta . '][timing][' . $day_key . '][enable]"]' => ['checked' => TRUE],
          ],
        ],
        '#name' => $removeItemName,
      ];
    }
    return $element;
  }

  /**
   * Validate the fields and convert them into a single value as text.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);

    $value['start_period_month'] = $value['period']['start_period']['month'];
    $value['start_period_day'] = $value['period']['start_period']['day'];
    $value['end_period_month'] = $value['period']['end_period']['month'];
    $value['end_period_day'] = $value['period']['end_period']['day'];
    unset($value['period']);

    if (empty($value['start_period_month']) || empty($value['start_period_day']) || empty($value['end_period_month']) || empty($value['end_period_day'])) {
      if (!(empty($value['start_period_month']) && empty($value['start_period_day']) && empty($value['end_period_month']) && empty($value['end_period_day']))) {
        $form_state->setError($element, $this->t('You must define the period completely'));
      }
    }
    if (!empty($value['start_period_month']) && !empty($value['start_period_day']) && !empty($value['end_period_month']) && !empty($value['end_period_day'])) {
      if ($value['start_period_month'] . '-' . $value['start_period_day'] > $value['end_period_month'] . '-' . $value['end_period_day']) {
        $form_state->setError($element, $this->t('The start period must be less or equal than the final period.'));
      }
      if (!empty($value['timing'])) {
        foreach ($value['timing'] as $day_key => $day_value) {
          $value[$day_key] = $day_value['enable'];
          unset($value['timing'][$day_key]['enable']);
          $schedules = $value['timing'][$day_key]['schedule'];
          unset($value['timing'][$day_key]['schedule']);
          if (empty($value[$day_key])) {
            $value['timing'][$day_key] = [];
          }
          else {
            unset($schedules['add']);
            unset($schedules['remove']);
            foreach ($schedules as $schedule_key => &$schedule) {
              if (
                (!isset($schedule['start_time_hour']) || $schedule['start_time_hour'] == '') ||
                (!isset($schedule['start_time_minute']) || $schedule['start_time_minute'] == '') ||
                (!isset($schedule['end_time_hour']) || $schedule['end_time_hour'] == '') ||
                (!isset($schedule['end_time_minute']) || $schedule['end_time_minute'] == '')
              ) {
                if (!(
                  (!isset($schedule['start_time_hour']) || $schedule['start_time_hour'] == '') &&
                  (!isset($schedule['start_time_minute']) || $schedule['start_time_minute'] == '') &&
                  (!isset($schedule['end_time_hour']) || $schedule['end_time_hour'] == '') &&
                  (!isset($schedule['end_time_minute']) || $schedule['end_time_minute'] == '')
                )) {
                  $form_state->setError($element, $this->t('You must define all the schedule timings completely'));
                }
                else {
                  unset($schedules[$schedule_key]);
                }
              }
              else {
                if (((($schedule['start_time_hour'] * 60) + $schedule['start_time_minute'])) >= (($schedule['end_time_hour'] * 60) + $schedule['end_time_minute'])) {
                  $form_state->setError($element, $this->t('The start time must be less than the final time.'));
                }
              }
            }
            $value['timing'][$day_key] = $schedules;
          }

        }
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * Return the checkbox base name.
   *
   * @param array $element
   *   The current element.
   *
   * @return string
   *   The base name.
   */
  protected function getCheckBaseNameByElement(array $element) {
    $fieldName = $this->fieldDefinition->getName();
    $nParents = count($element["#field_parents"]);
    if ($nParents > 0) {
      $resp = $element["#field_parents"][0];
      for ($i = 1; $i < $nParents; ++$i) {
        $resp .= '[' . $element["#field_parents"][$i] . ']';
      }
      return $resp . '[' . $fieldName . ']';
    }
    else {
      return $fieldName;
    }
  }

  /**
   * Return the base name calculated by the parents.
   *
   * @param array $parents
   *   The widget parents.
   *
   * @return string
   *   The base name.
   */
  protected static function getBaseNameByParents(array $parents) {
    $offset = count($parents);
    $field = $parents[$offset - 6];
    $delta = $parents[$offset - 5];
    $day_key = $parents[$offset - 3];
    return $field . '_' . $delta . '_' . $day_key;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function scheduleNewItem(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    $baseName = self::getBaseNameByParents($parents);
    $deltas = $form_state->get($baseName . '_deltas') ? $form_state->get($baseName . '_deltas') : 0;
    if (!empty($deltas)) {
      $deltas++;
    }
    else {
      $deltas = 1;
    }
    $form_state->set($baseName . '_deltas', $deltas);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function scheduleNewItemAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    $item = NestedArray::getValue($form, $parents);
    return $item;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function scheduleRemoveItem(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    $baseName = self::getBaseNameByParents($parents);
    $deltas = $form_state->get($baseName . '_deltas') ? $form_state->get($baseName . '_deltas') : 0;
    if (!empty($deltas)) {
      $deltas--;
    }
    else {
      $deltas = 0;
    }
    $form_state->set($baseName . '_deltas', $deltas);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function scheduleRemoveItemAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    $item = NestedArray::getValue($form, $parents);
    return $item;
  }

}
