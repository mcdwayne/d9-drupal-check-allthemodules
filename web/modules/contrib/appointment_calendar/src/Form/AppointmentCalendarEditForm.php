<?php

namespace Drupal\appointment_calendar\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AppointmentCalendarEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_calendar_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $from_date = \Drupal::request()->query->get('date');
    // Date edit page.
    if ($from_date != '') {
      $form['appointment_slot_date'] = [
        '#type' => 'datetime',
        '#title' => $this->t('Date'),
        '#date_date_element' => 'date',
        '#date_time_element' => 'none',
        '#default_value' => DrupalDateTime::createFromTimestamp($from_date),
        '#disabled' => TRUE,
      ];
      // Fetching Slot previous capacity filled.
      $capacity = appointment_calendar_slot_capacity($from_date);
      if ($capacity) {
        $i = 1;
        // Show slots and capacity.
        foreach (json_decode($capacity) as $key => $value) {
          // Check if any appointment booked.
          $slot_check = appointment_calendar_slot_capacity_value($key);
          $form['time_slot_' . $i] = [
            '#type' => 'textfield',
            '#title' => Html::escape('Time Slot ' . $i . ' :'),
            '#description' => t('Ex: 10:00-11:00, 13:00-14:00, etc.,'),
            '#default_value' => $key,
            '#prefix' => '<div class="time-slot-field-form">',
          ];
          if ($slot_check > 0) {
            $form['time_slot_' . $i]['#disabled'] = TRUE;
            $form['time_slot_' . $i]['#description'] = t('<b>Slot :i </b>booked atleast once', [':i' => $i]);
          }
          $form['time_slot_' . $i . '_capacity'] = [
            '#type' => 'textfield',
            '#title' => Html::escape('Slot ' . $i . ' Capacity'),
            '#description' => t('Only Numeric'),
            '#default_value' => $value,
            '#suffix' => '</div>',
          ];
          $i++;
        }
      }
      $form['appointment_slot'] = [
        '#type' => 'textfield',
        '#title' => $this->t('No of Extra Slots:'),
      ];
      $values = $form_state->getValues();
      // Display Extra slots.
      if (!empty($values)) {
        $extra_slots = $values['appointment_slot'];
        $extra_slots += $i - 1;
        for ($j = $i; $j <= $extra_slots; $j++) {
          $form['slots']['time_slot_' . $j] = [
            '#type' => 'textfield',
            '#title' => Html::escape('Time Slot ' . $j . ' :'),
            '#description' => t('Ex: 10:00-11:00, 13:00-14:00, etc.,'),
            '#default_value' => '',
            '#prefix' => '<div class="time-slot-field-form">',
          ];
          $form['slots']['time_slot_' . $j . '_capacity'] = [
            '#type' => 'textfield',
            '#title' => Html::escape('Slot ' . $j . ' Capacity'),
            '#description' => t('Only Numeric'),
            '#default_value' => '',
            '#suffix' => '</div>',
          ];
        }
        $j++;
      }
      $form['add_more'] = [
        '#type' => 'submit',
        '#value' => t('Add More Slots'),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];
    if ($op == $this->t('Submit')) {
      $date = $values['appointment_slot_date']->getTimestamp();
      $capacity = appointment_calendar_slot_capacity($date);
      $slots = count((array) json_decode($capacity));
      if (!empty($values['appointment_slot'])) {
        $slots += $values['appointment_slot'];
      }
      // Time slot and Capacity Validation.
      for ($i = 1; $i <= $slots; $i++) {
        $booked_capacity = '';
        $time_slot = $values['time_slot_' . $i];
        $time_capacity = $values['time_slot_' . $i . '_capacity'];
        $regex = '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]-(?:[01][0-9]|2[0-3]):[0-5][0-9]$/';
        // Timeslot.
        if (!preg_match($regex, $time_slot)) {
          $form_state->setErrorByName('time_slot_' . $i, t('Time slot should be in between 00:00-23:59 (in between 24 hrs)'));
        }
        // Slot Capacity.
        if ($time_capacity < 0) {
          $form_state->setErrorByName('time_slot_' . $i . '_capacity', t('Slot Capacity should be greater than 0'));
        }
        $booked_capacity = appointment_calendar_slot_capacity_value($time_slot);
        if ($time_capacity < $booked_capacity) {
          $form_state->setErrorByName('time_slot_' . $i, t('Already :slots Appointment(s) booked in this Slot. So it should not be less than :slots', [':slots' => $booked_capacity]));
        }
        // Checking duplicate slots.
        $time_slots_check[] = $time_slot;
        $vals = array_count_values($time_slots_check);
        if ($vals[$time_slot] > 1) {
          $form_state->setErrorByName('time_slot_' . $i, t('Time slot cannot redeclare twice or more.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];
    if ($op == $this->t('Add More Slots')) {
      $form_state->setRebuild();
    }
    // Pass values to url.
    if ($op == $this->t('Submit')) {
      $date = $values['appointment_slot_date']->getTimestamp();
      $capacity = appointment_calendar_slot_capacity($date);
      $slots = count((array) json_decode($capacity));
      if (!empty($values['appointment_slot'])) {
        $slots += $values['appointment_slot'];
      }
      // Fetching Timeslot and capacity values.
      for ($i = 1; $i <= $slots; $i++) {
        $time_slot = $values['time_slot_' . $i];
        $time_capacity = $values['time_slot_' . $i . '_capacity'];
        $slots_save[$time_slot] = $time_slot;
        $slots_capacity[$time_slot] = $time_capacity;
      }
      // Saving date with time slots and capacity.
      $db_conn = \Drupal::database();
      $db_conn->merge('appointment_date')
        ->key(
          ['date' => $date,]
        )
        ->fields(
          [
            'no_slots' => $slots,
            'slot_values' => json_encode($slots_save),
            'slot_capacity' => json_encode($slots_capacity),
          ]
        )->execute();
      drupal_set_message(t('Changes made successfully'));
      $form_state->setRedirect('appointment_calendar.list_page');
    }
  }

}
