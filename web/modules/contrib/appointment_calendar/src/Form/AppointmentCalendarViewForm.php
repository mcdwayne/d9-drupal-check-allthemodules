<?php

namespace Drupal\appointment_calendar\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AppointmentCalendarViewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_calendar_view_form';
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
      $date = date('Y-m-d', $from_date);
      // Fetching Slot previous capacity filled.
      $capacity = appointment_calendar_slot_capacity($from_date);
      $headers = [t('Slot Time'), t('Alloted Slots'), t('Booked Slots'), t('Status')];
      foreach (json_decode($capacity) as $key => $value) {
        // Check if any appointment booked.
        $db_conn = \Drupal::database();
        $query = $db_conn->select('node__field_appointment_date', 'ad');
        $query->leftJoin('appointment_slots', 'ap', 'ad.entity_id = ap.nid');
        $query->leftJoin('node_field_data', 'node_data', 'node_data.nid = ap.nid');
        $query->fields('ad', ['field_appointment_date_value']);
        $query->fields('ap', ['slot']);
        $query->condition('ad.field_appointment_date_value', $date, '=');
        $query->condition('ap.slot', $key, '=');
        $query->condition('node_data.status', 1);
        $result = $query->execute()->fetchAll();
        if (count($result) >= $value) {
          $row[$key]['slot'] = $key;
          $row[$key]['no_slots'] = $value;
          $row[$key]['booked_slots'] = count($result);
          $row[$key]['status'] = 'Booked';
        }
        else {
          $row[$key]['slot'] = $key;
          $row[$key]['no_slots'] = $value;
          $row[$key]['booked_slots'] = count($result);
          $row[$key]['status'] = 'Free';
        }
        $form['data'] = [
          '#theme' => 'table',
          '#header' => $headers,
          '#rows' => $row,
        ];
      }
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
