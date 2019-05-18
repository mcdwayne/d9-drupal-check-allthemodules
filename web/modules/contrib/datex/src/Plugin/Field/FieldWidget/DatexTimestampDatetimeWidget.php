<?php

namespace Drupal\datex\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datex\Datex\DatexDrupalDateTime;

/**
 * Class DatexTimestampDatetimeWidget
 *
 * @FieldWidget(
 *   id = "datetime_timestamp",
 *   label = @Translation("Datex Datetime Timestamp"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *   }
 * )
 */
class DatexTimestampDatetimeWidget extends TimestampDatetimeWidget {

  function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $cal = datex_factory();
    if (!$cal) {
      return $element;
    }

    $d = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    if (!empty($d) && !($d instanceof DatexDrupalDateTime) && $d instanceof DrupalDateTime) {
      $element['#default_value'] = DatexDrupalDateTime::convert($d);
    }

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', [
      '%format' => $cal->format($date_format . ' ' . $time_format),
    ]);

    return $element;
  }

}
