<?php

namespace Drupal\outlook_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the table form for events display.
 */
class OutlookEventForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outlook_calendar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'outlook_calendar/outlook_calendar.sort';

    $header = [
      'title' => $this->t('TITLE'),
      'organizer' => $this->t('ORGANIZER'),
      'location' => $this->t('LOCATION'),
      'start_ist' => $this->t('START TIME'),
      'end_ist' => $this->t('END TIME'),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => outlook_calendar_display(),
      '#empty' => $this->t('No events found. This could either mean there are no events scheduled or the credentials could be invalid'),
      '#attributes' => ['id' => 'outlook-cal'],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {

  }

}
