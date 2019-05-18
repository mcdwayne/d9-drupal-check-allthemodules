<?php

namespace Drupal\calendar_systems\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;
use Drupal\calendar_systems\CalendarSystems\CalendarSystemsDrupalDateTime;

/**
 * Replaces core's widget with a localizable one.
 *
 * @FieldWidget(
 *   id = "datetime_default",
 *   label = @Translation("CalendarSystems date and time"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class CalendarSystemsDateTimeDefaultWidget extends DateTimeDefaultWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $cal = calendar_systems_factory();
    if (!$cal) {
      return $element;
    }

    if ($items[$delta] && $items[$delta]->date) {
      $date = $items[$delta]->date;
      $date = CalendarSystemsDrupalDateTime::convert($date);
      $element['value']['#default_value'] = $date;
    }

    $element['value']['#date_date_element'] = 'text';
    return $element;
  }

}
