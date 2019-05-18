<?php

namespace Drupal\appointment_calendar\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AppointmentCalendarForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_calendar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default year.
    $default_year = date('Y', time());
    $form['appointment_from_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Appointment From date'),
      '#default_value' => new DrupalDateTime('now'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => $default_year . ':+3',
      '#datepicker_options' => ['minDate' => 0],
      '#required' => TRUE,
    ];
    $form['appointment_to_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Appointment To date'),
      '#default_value' => new DrupalDateTime('now'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => $default_year . ':+3',
      '#datepicker_options' => ['minDate' => 0],
      '#required' => TRUE,
    ];
    $form['appointment_slot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No of Slots:'),
      '#size' => 10,
      '#maxlength' => 10,
      '#required' => TRUE,
    ];
    $form['appointment_fill'] = [
      '#type' => 'button',
      '#value' => $this->t('Fill Slots'),
      '#weight' => 36,
      '#ajax' => [
        'callback' => '::appointment_calendar_filltime_slots_callback_form',
        'wrapper' => 'time-slot-check',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $no_slots = isset($form_state->getValues()['appointment_slot']) ? $form_state->getValues()['appointment_slot'] : 0;
    $form['slots']['#prefix'] = '<div id="time-slot-check">';
    for ($i = 1; $i <= $no_slots; $i++) {
      $form['slots']['time_slot_' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Time Slot ' . $i . ' :'),
        '#description' => $this->t('Ex: 10:00-11:00, 13:00-14:00, etc.,'),
      ];
      $form['slots']['time_slot_' . $i . '_capacity'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Slot ' . $i . ' Capacity'),
        '#description' => $this->t('Only Numeric'),
      ];
    }
    $form['slots']['#suffix'] = '</div>';
    $form['slots']['#weight'] = 39;
    if ($no_slots != 0) {
      $form['slots']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
      $form['slots']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
      ];
    }
    return $form;
  }

  /**
   * Ajax callback function to show timeslots.
   */
  public function appointment_calendar_filltime_slots_callback_form(array &$form, FormStateInterface $form_state) {
    return $form['slots'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $db_conn = \Drupal::database();
    $op = (string) $values['op'];
    if ($op == $this->t('Reset')) {
      $form_state->setRedirect('appointment_calendar.subscribers');
    }
    if ($op == $this->t('Submit')) {
      $start_date = $values['appointment_from_date']->getTimestamp();
      $end_date = $values['appointment_to_date']->getTimestamp();
      // Getting all dates in between Start and End Dates.
      $dates = appointment_calendar_daysbetween($start_date, $end_date);
      $check_count = 0;
      // Checking for already filled slots.
      foreach ($dates as $each_date) {
        $date_check = $db_conn->select('appointment_date', 'ad');
        $date_check->fields('ad', ['date']);
        $date_check->condition('date', $each_date, '=');
        $date_result = $date_check->execute()->fetchField();
        if (!empty($date_result)) {
          $check_count++;
          $date = date('Y-m-d', $each_date);
          drupal_set_message($date . ' Already filled', 'error');
        }
      }
      if ($check_count > 0) {
        $form_state->setErrorByName('appointment_from_date', t('Verify and try adding again'));
      }
      // Date Validation.
      if ($start_date > $end_date) {
        $form_state->setErrorByName('appointment_to_date', t('End Date Should be greater than Start Date'));
      }
      if ($start_date < strtotime(date('Y-m-d', time()))) {
        $form_state->setErrorByName('appointment_start_date', t('Start Date Should be greater than Today(s) Date'));
      }
      $slots = $values['appointment_slot'];
      // Time slot and Capacity Validation.
      $time_slots_check = [];
      for ($i = 1; $i <= $slots; $i++) {
        $time_slot = $values['time_slot_' . $i];
        $time_capacity = $values['time_slot_' . $i . '_capacity'];
        $regex = '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]-(?:[01][0-9]|2[0-3]):[0-5][0-9]$/';
        // Timeslot.
        if (!preg_match($regex, $time_slot)) {
          $form_state->setErrorByName('time_slot_' . $i, t('Time slot format should be 00:00-00:00 (in between 24 hrs).'));
        }
        // Slot Capacity.
        if ($time_capacity < 0) {
          $form_state->setErrorByName('time_slot_' . $i . '_capacity', t('Slot Capacity should be greater than 0.'));
        }
        // Timeslot Check.
        if (empty($time_capacity)) {
          $form_state->setErrorByName('time_slot_' . $i . '_capacity', t('Fill time slot capacity.'));
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
    $db_conn = \Drupal::database();
    $op = (string) $values['op'];
    if ($op == $this->t('Submit')) {
      $start_date = $values['appointment_from_date']->getTimestamp();
      $end_date = $values['appointment_to_date']->getTimestamp();
      $slots = $values['appointment_slot'];
      // Fetching Timeslot and capacity values.
      for ($i = 1; $i <= $slots; $i++) {
        $time_slot = $values['time_slot_' . $i];
        $time_capacity = $values['time_slot_' . $i . '_capacity'];
        $slots_save[$time_slot] = $time_slot;
        $slots_capacity[$time_slot] = $time_capacity;
      }
      ksort($slots_save);
      ksort($slots_capacity);
      // Getting all dates in between Start and End Dates.
      $dates = appointment_calendar_daysbetween($start_date, $end_date);
      // Saving date with time slots and capacity.
      foreach ($dates as $each_date) {
        $db_conn->merge('appointment_date')->key(['date' => $each_date])->fields([
          'no_slots' => $slots,
          'slot_values' => json_encode($slots_save),
          'slot_capacity' => json_encode($slots_capacity),
        ])->execute();
      }
      // Redirect to list page.
      drupal_set_message(t('Slot(s) created successfully'));
    }
  }

}
