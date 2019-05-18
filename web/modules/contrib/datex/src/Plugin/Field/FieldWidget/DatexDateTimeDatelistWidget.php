<?php

namespace Drupal\datex\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDatelistWidget;
use Drupal\datex\Datex\DatexDrupalDateTime;

/**
 * Replaces core's widget with a localizable one.
 *
 * @FieldWidget(
 *   id = "datetime_datelist",
 *   label = @Translation("Datex select list"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DatexDateTimeDatelistWidget extends DateTimeDatelistWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $cal = datex_factory();
    if (!$cal) {
      return $element;
    }

    if ($items[$delta] && $items[$delta]->date) {
      $date = $items[$delta]->date;
      $date = DatexDrupalDateTime::convert($date);
      $element['value']['#default_value'] = $date;
    }

    return $element;
  }

}
