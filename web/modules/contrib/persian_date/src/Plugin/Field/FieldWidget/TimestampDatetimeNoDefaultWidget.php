<?php

namespace Drupal\persian_date\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\persian_date\Element\PersianDateTime;
use Drupal\scheduler\Plugin\Field\FieldWidget\TimestampDatetimeNoDefaultWidget as BaseWidget;

/**
 * Plugin implementation of the 'datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp_no_default",
 *   label = @Translation("Datetime Timestamp for Scheduler"),
 *   description = @Translation("An optional datetime field. Does not provide a default time if left blank. Defined by Scheduler module."),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class TimestampDatetimeNoDefaultWidget extends BaseWidget
{

  /**
   * Callback function to add default time to the input date if needed.
   *
   * This will intercept the user input before form validation is processed.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      $date_input = $element['#date_date_element'] != 'none' && !empty($input['date']) ? $input['date'] : '';
      $time_input = $element['#date_time_element'] != 'none' && !empty($input['time']) ? $input['time'] : '';
      // If there is an input date but no time and the date-only option is on
      // then set the input time to the default specified by scheduler options.
      $config = \Drupal::config('scheduler.settings');
      if (!empty($date_input) && empty($time_input) && $config->get('allow_date_only')) {
        $input['time'] = $config->get('default_time');
      }
    }
    // Chain on to the standard valueCallback for Datetime as we do not want to
    // duplicate that core code here.
    return PersianDateTime::valueCallback($element, $input, $form_state);
  }

}
