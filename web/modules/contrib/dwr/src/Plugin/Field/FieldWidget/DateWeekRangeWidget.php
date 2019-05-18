<?php

namespace Drupal\dwr\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;

/**
 * Class implementation for the 'date_week_range' widget.
 *
 * @FieldWidget(
 *   id = "date_week_range",
 *   label = @Translation("Date and time week range"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateWeekRangeWidget extends DateRangeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $date_type = 'date';
    $time_type = 'none';
    $date_format = DATETIME_DATE_STORAGE_FORMAT;
    $time_format = '';

    $element['#element_validate'][] = [$this, 'validateWeekRange'];

    $field_name = $items->getFieldDefinition()->getName();
    $statusId = Html::getId("edit-$field_name-$delta-status");

    $element['datepicker'] = [
      '#markup' => '<div class="week-picker"></div><span id="' . $statusId . '"></span>',
      '#weight' => -1,
      '#attached' => [
        'library' => [
          'dwr/dwr',
        ],
        'drupalSettings' => [
          'date_week_range' => [
            'fieldName' => Html::getId("edit-$field_name-$delta"),
            'valueFieldName' => Html::getId("edit-$field_name-$delta-value"),
            'endValueFieldName' => Html::getId("edit-$field_name-$delta-end_value"),
            'statusID' => $statusId,
          ],
        ],
      ],
    ];

    $element['value'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ] + $element['value'];

    $element['end_value'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ] + $element['end_value'];

    if ($items[$delta]->start_date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $items[$delta]->start_date;
      $element['value']['#default_value'] = $start_date->format(DATETIME_DATE_STORAGE_FORMAT);
    }

    if ($items[$delta]->end_date) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $items[$delta]->end_date;
      $element['end_value']['#default_value'] = $end_date->format(DATETIME_DATE_STORAGE_FORMAT);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Overwrite the original validation because the values will come
    // parsed in a different way.
    $timezone = !empty($element['#date_timezone']) ? $element['#date_timezone'] : NULL;
    $start_date = DrupalDateTime::createFromFormat(DATETIME_DATE_STORAGE_FORMAT, $element['value']['#value'], $timezone);
    $end_date = DrupalDateTime::createFromFormat(DATETIME_DATE_STORAGE_FORMAT, $element['end_value']['#value'], $timezone);

    if ($start_date instanceof DrupalDateTime && $end_date instanceof DrupalDateTime) {
      if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
        $interval = $start_date->diff($end_date);
        if ($interval->invert === 1) {
          $form_state->setError($element, $this->t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

  /**
   * Ensure that the date range is a week.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateWeekRange(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $timezone = !empty($element['#date_timezone']) ? $element['#date_timezone'] : NULL;
    $start_date = DrupalDateTime::createFromFormat(DATETIME_DATE_STORAGE_FORMAT, $element['value']['#value'], $timezone);
    $end_date = DrupalDateTime::createFromFormat(DATETIME_DATE_STORAGE_FORMAT, $element['end_value']['#value'], $timezone);

    if ($start_date instanceof DrupalDateTime && $end_date instanceof DrupalDateTime) {
      $interval = $start_date->diff($end_date);
      if ($interval->days != 6) {
        $form_state->setError($element, $this->t("@title: The selected dates don't compose a week.", ['@title' => $element['#title']]));
      }
    }
  }

}
