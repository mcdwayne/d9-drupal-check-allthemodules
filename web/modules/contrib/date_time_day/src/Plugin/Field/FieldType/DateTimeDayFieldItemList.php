<?php

namespace Drupal\date_time_day\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList;

/**
 * Represents a configurable entity datetimeday field.
 */
class DateTimeDayFieldItemList extends DateTimeFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();

      $element = parent::defaultValuesForm($form, $form_state);
      // Start date properties.
      $element['default_start_time_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Default start time type'),
        '#description' => $this->t('Set a default value for the start time.'),
        '#default_value' => isset($default_value[0]['default_start_time_type']) ? $default_value[0]['default_start_time_type'] : '',
        '#options' => [
          static::DEFAULT_VALUE_NOW => $this->t('Current date'),
          static::DEFAULT_VALUE_CUSTOM => $this->t('Relative date'),
        ],
        '#empty_value' => '',
      ];

      $element['default_start_time'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Relative default start time value'),
        '#description' => $this->t("Describe a time by reference to the current day, like '+90 days' (90 days from the day the field is created) or '+1 Saturday' (the next Saturday). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
        '#default_value' => (isset($default_value[0]['default_start_time_type']) && $default_value[0]['default_start_time_type'] == static::DEFAULT_VALUE_CUSTOM) ? $default_value[0]['default_start_time'] : '',
        '#states' => [
          'visible' => [
            ':input[id="edit-default-value-input-default-start-time-type"]' => ['value' => static::DEFAULT_VALUE_CUSTOM],
          ],
        ],
      ];
      // Start date properties.
      $element['default_end_time_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Default end time type'),
        '#description' => $this->t('Set a default value for the end time.'),
        '#default_value' => isset($default_value[0]['default_end_time_type']) ? $default_value[0]['default_end_time_type'] : '',
        '#options' => [
          static::DEFAULT_VALUE_NOW => $this->t('Current date'),
          static::DEFAULT_VALUE_CUSTOM => $this->t('Relative date'),
        ],
        '#empty_value' => '',
      ];

      $element['default_end_time'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Relative default end time value'),
        '#description' => $this->t("Describe a time by reference to the current day, like '+90 days' (90 days from the day the field is created) or '+1 Saturday' (the next Saturday). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
        '#default_value' => (isset($default_value[0]['default_end_time_type']) && $default_value[0]['default_end_time_type'] == static::DEFAULT_VALUE_CUSTOM) ? $default_value[0]['default_end_time'] : '',
        '#states' => [
          'visible' => [
            ':input[id="edit-default-value-input-default-end-time-type"]' => ['value' => static::DEFAULT_VALUE_CUSTOM],
          ],
        ],
      ];

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_date']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_date', $this->t('The relative default date value entered is invalid.'));
      }
    }

    if ($form_state->getValue(['default_value_input', 'default_start_time_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_start_time']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_start_time', $this->t('The relative default start time value entered is invalid.'));
      }
    }

    if ($form_state->getValue(['default_value_input', 'default_end_time_type']) == static::DEFAULT_VALUE_CUSTOM) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_end_time']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_end_time', $this->t('The relative default end time value entered is invalid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_date_type']) || $form_state->getValue(['default_value_input', 'default_end_date_type'])) {
      if ($form_state->getValue(['default_value_input', 'default_date_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_date'], static::DEFAULT_VALUE_NOW);
      }
      if ($form_state->getValue(['default_value_input', 'default_start_time_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_start_time'], static::DEFAULT_VALUE_NOW);
      }
      if ($form_state->getValue(['default_value_input', 'default_end_time_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_end_time'], static::DEFAULT_VALUE_NOW);
      }
      return [$form_state->getValue('default_value_input')];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // Explicitly call the base class so that we can get the default value
    // types.
    $default_value = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    // Allow either the start or end time to have a default, but not require
    // defaults for both.
    if (!empty($default_value[0]['default_date_type']) || !empty($default_value[0]['default_start_time_type']) || !empty($default_value[0]['default_end_time_type'])) {
      // A default value should be in the format and timezone used for date
      // storage.
      $storage_format = $definition->getSetting('datetime_type') == DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT ? DateTimeDayItem::DATE_TIME_DAY_H_I_FORMAT_STORAGE_FORMAT : DateTimeDayItem::DATEDAY_TIME_TYPE_SECONDS_FORMAT;
      $default_values = [[]];
      if (!empty($default_value[0]['default_date_type'])) {
        $date = new DrupalDateTime($default_value[0]['default_date'], DATETIME_STORAGE_TIMEZONE);
        $value = $date->format(DATETIME_DATE_STORAGE_FORMAT);
        $default_values[0]['value'] = $value;
        $default_values[0]['date'] = $date;
      }

      if (!empty($default_value[0]['default_start_time_type'])) {
        $start_time = new DrupalDateTime($default_value[0]['default_start_time'], DATETIME_STORAGE_TIMEZONE);
        $start_time_value = $start_time->format($storage_format);
        $default_values[0]['start_time_value'] = $start_time_value;
        $default_values[0]['start_time'] = $start_time;
      }

      if (!empty($default_value[0]['default_end_time_type'])) {
        $end_time = new DrupalDateTime($default_value[0]['default_end_time'], DATETIME_STORAGE_TIMEZONE);
        $end_time_value = $end_time->format($storage_format);
        $default_values[0]['end_time_value'] = $end_time_value;
        $default_values[0]['end_time'] = $end_time;
      }

      $default_value = $default_values;
    }

    return $default_value;
  }

}
