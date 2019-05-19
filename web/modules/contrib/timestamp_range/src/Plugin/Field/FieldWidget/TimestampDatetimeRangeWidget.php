<?php

namespace Drupal\timestamp_range\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

/**
 * Plugin implementation of the 'datetime timestamp range' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp_range",
 *   label = @Translation("Datetime Timestamp Range"),
 *   field_types = {
 *     "timestamp_range"
 *   }
 * )
 */
class TimestampDatetimeRangeWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $default_value_end = isset($items[$delta]->end_value) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_value) : '';

    $element['value'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start date'),
      '#default_value' => $default_value,
      '#date_year_range' => '1902:2037',
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => $element['#required']
    ];

    $element['end_value'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End date'),
      '#default_value' => $default_value_end,
      '#date_year_range' => '1902:2037',
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => $element['#required']
    ];

    $element['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', ['%format' => Datetime::formatExample($date_format . ' ' . $time_format)]);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$item) {
      if (is_null($item['value']) && is_null($item['end_value'])) {
        unset($values[$delta]);
        continue;
      }

      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }
      else {
        $date = NULL;
      }

      if (isset($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
        $date_end = $item['end_value'];
      }
      elseif (isset($item['end_value']['object']) && $item['end_value']['object'] instanceof DrupalDateTime) {
        $date_end = $item['end_value']['object'];
      }
      else {
        $date_end = NULL;
      }

      if ($date) {
        $item['value'] = $date->getTimestamp();
      }
      if ($date_end) {
        $item['end_value'] = $date_end->getTimestamp();
      }
    }
    return $values;
  }

}
