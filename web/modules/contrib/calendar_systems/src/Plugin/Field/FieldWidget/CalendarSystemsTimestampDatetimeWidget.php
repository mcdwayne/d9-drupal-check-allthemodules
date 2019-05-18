<?php

namespace Drupal\calendar_systems\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\calendar_systems\CalendarSystems\CalendarSystemsDrupalDateTime;

/**
 * Class CalendarSystemsTimestampDatetimeWidget
 *
 * @FieldWidget(
 *   id = "datetime_timestamp",
 *   label = @Translation("CalendarSystems Datetime Timestamp"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *   }
 * )
 */
class CalendarSystemsTimestampDatetimeWidget extends TimestampDatetimeWidget {

  function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $cal = calendar_systems_factory();
    if (!$cal) {
      return $element;
    }

    $d = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    if (!empty($d) && !($d instanceof CalendarSystemsDrupalDateTime) && $d instanceof DrupalDateTime) {
      $element['#default_value'] = CalendarSystemsDrupalDateTime::convert($d);
    }

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', [
      '%format' => $cal->format($date_format . ' ' . $time_format),
    ]);

    return $element;
  }

}
