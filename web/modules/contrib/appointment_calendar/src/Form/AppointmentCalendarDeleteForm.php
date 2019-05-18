<?php

namespace Drupal\appointment_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AppointmentCalendarDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_calendar_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $db_conn = \Drupal::database();
    $from_date = \Drupal::request()->query->get('date');
    // Checking for Booked slots.
    // If slots booked in particular date disable option for delete.
    if ($from_date != '') {
      $date = date('Y-m-d', $from_date);
      $delete_query = $db_conn->select('node__field_appointment_date', 'ad');
      $delete_query->fields('ad', ['field_appointment_date_value']);
      $delete_query->condition('field_appointment_date_value', '%' . db_like($date) . '%', 'LIKE');
      $delete_result = $delete_query->execute()->fetchAll();
      if (count($delete_result) >= 1) {
        $form['date'] = [
          '#markup' => $this->t('Unable to delete ' . $date .
            '. Appointment already booked in selected date<br>If you still want to delete the selected date, delete timeslots booked and retry<br> '),
        ];
        $form['return'] = [
          '#type' => 'submit',
          '#value' => t('Return'),
        ];
      }
      else {
        $form['date_markup'] = [
          '#markup' => $this->t('Are you sure to delete <b>:date</b>?<br>Note:All filled timeslots also will be deleted.<br>', array(':date' => $date)),
        ];
        $form['date'] = [
          '#type' => 'hidden',
          '#value' => $from_date,
        ];
        $form['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Yes'),
        ];
        $form['no'] = [
          '#type' => 'submit',
          '#value' => $this->t('No'),
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
    $values = $form_state->getValues();
    $db_conn = \Drupal::database();
    $op = (string) $values['op'];
    // Delete Slot.
    if ($op == $this->t('Yes')) {
      $db_conn->delete('appointment_date')
        ->condition('date', $values['date'])
        ->execute();
      drupal_set_message(t('Selected Date deleted successfully'));
      $form_state->setRedirect('appointment_calendar.list_page');
    }
    // Go-to Listing Page.
    if (($op == $this->t('No')) || ($op == $this->t('Return'))) {
      $form_state->setRedirect('appointment_calendar.list_page');
    }
  }

}
