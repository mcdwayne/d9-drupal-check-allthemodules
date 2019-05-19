<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Plugin\Field\FieldWidget\Hour.
 */

namespace Drupal\station_schedule\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @todo.
 *
 * @FieldWidget(
 *   id = "station_schedule_hour",
 *   label = @Translation("Hour (schedule)"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class Hour extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => $items[$delta]->value ?: $this->getDefaultValue($items, $delta),
      '#options' => $this->hourOptions($this->getFieldSetting('hour_type')),
      '#required' => TRUE,
      '#description' => $this->t('This is the time of day when your programming starts.'),
    ];

    return $element;
  }

  /**
   * @todo.
   *
   * @param string $type
   *   Either 'start' or 'end'. Any other string will be ignored.
   *
   * @return array
   *   An array of hour options.
   */
  protected function hourOptions($type) {
    // If type is "start", we'll provide 12am-11pm. If type is "end", we'll
    // provide 1am through noon the next day.
    switch ($type) {
      case 'start':
        $earliest = 0;
        $latest = 24;
        break;

      case 'end':
        $earliest = 1;
        $latest = 36;
        break;

      default:
        return [];
    }
    $hour_options = [];
    for ($i = $earliest; $i < $latest; $i++) {
      if ($i == 0 || $i == 24) {
        $hour = '12';
        $suffix = 'am';
      }
      elseif ($i == 12) {
        $hour = '12';
        $suffix = 'pm';
      }
      elseif ($i > 12) {
        if ($i > 24) {
          // This is during the morning of the next day, so subtract 24 and
          // attach the suffix 'tomorrow'.
          $hour = $i - 24;
          $suffix = 'am ' . $this->t('the next day');
        }
        else {
          // This is after noon on the current day, so subtract 12.
          $hour = $i - 12;
          $suffix = 'pm';
        }
      }
      else {
        // This is during the morning of the current day.
        $hour = $i;
        $suffix = 'am';
      }
      $hour_options[$i] = $hour . ':00' . $suffix;
    }
    return $hour_options;
  }

  /**
   * @todo Why isn't this provided by WidgetBase?
   */
  protected function getDefaultValue(FieldItemListInterface $items, $delta, $key = 'value') {
    return $this->fieldDefinition->getDefaultValue($items->getEntity())[$delta][$key];
  }

}
