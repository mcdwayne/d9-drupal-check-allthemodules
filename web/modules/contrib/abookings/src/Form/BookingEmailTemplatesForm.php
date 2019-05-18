<?php

namespace Drupal\abookings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BookingEmailTemplatesForm.
 *
 * @package Drupal\abookings\Form
 */
class BookingEmailTemplatesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'abookings.bookingemailtemplates',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'booking_email_templates_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('abookings.bookingemailtemplates');

    $form['codes'] = [
      '#type' => 'details',
      '#title' => $this->t('Codes you can use'),
      'description' => [
        '#markup' => "<p>Use these codes to include information about the booking or bookable unit in the emails that get sent to the guest.</p>"
        . "<p>For example, if you add the <code>{checkin_date}</code> code to a template, that code will be replaced with the date that the guests should check-in.</p>"
      ],
    ];

    $codes_string =
        '<h3>Settings</h3>'
      . implode(' &nbsp; ', array_keys(get_codes()['settings']))
      . '<h3>Bookable</h3>'
      . implode(' &nbsp; ', array_keys(get_codes()['bookable']))
      . '<h3>Booking</h3>'
      . implode(' &nbsp; ', array_keys(get_codes()['booking']))
    ;
    // @todo Use codes' descriptions as tooltips (HTML title attribute).

    $form['codes']['codes'] = [
      '#markup' => '<p>' . $codes_string . '</p>',
    ];

    // To guests

    $form['to_guests'] = [
      '#markup' => '<h2>Templates for emails to guests</h2>',
    ];

    // 1. Provisional booking placed

    $form['provis_booking_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Provisional booking placed'),
      'description' => [
        '#markup' => "<p>Email that is sent when a guest makes a booking.</p>"
      ],
    ];
    $form['provis_booking_guest']['provis_booking_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('provis_booking_guest_subject'),
    ];
    $form['provis_booking_guest']['provis_booking_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t('The body of the email that will be sent to the guest after succesfully placing a provisional booking.'),
      '#default_value' => $config->get('provis_booking_guest_body'),
    ];

    // 2. Reminder about deposit

    $form['deposit_reminder_booking_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Reminder about deposit'),
      'description' => [
        '#markup' => "<p>Email that is sent to remind guests to pay the deposit.</p>"
      ],
    ];
    $form['deposit_reminder_booking_guest']['deposit_reminder_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('deposit_reminder_guest_subject'),
    ];
    $form['deposit_reminder_booking_guest']['deposit_reminder_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $config->get('deposit_reminder_guest_body'),
    ];

    // 3. Booking expired

    $form['expired_booking_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Booking expired'),
      'description' => [
        '#markup' => "<p>Email that is sent when the guest's booking has expired due to not paying the deposit.</p>"
      ],
    ];
    $form['expired_booking_guest']['expired_booking_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('expired_booking_guest_subject'),
    ];
    $form['expired_booking_guest']['expired_booking_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $config->get('expired_booking_guest_body'),
    ];

    // 4. Booking confirmed

    $form['confirmed_booking_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Booking confirmed'),
      'description' => [
        '#markup' => "<p>Email that is sent when a booking's status changes to 'confirmed'.</p>"
      ],
    ];
    $form['confirmed_booking_guest']['confirmed_booking_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('confirmed_booking_guest_subject'),
    ];
    $form['confirmed_booking_guest']['confirmed_booking_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $config->get('confirmed_booking_guest_body'),
    ];

    // 5. Pre-arrival

    $form['prearrival_booking_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Pre-arrival'),
      'description' => [
        '#markup' => "<p>Email that is sent just before the guest arrives.</p>"
      ],
    ];
    $form['prearrival_booking_guest']['prearrival_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('prearrival_guest_subject'),
    ];
    $form['prearrival_booking_guest']['prearrival_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $config->get('prearrival_guest_body'),
    ];

    // 6. Feedback about booking

    $form['booking_feedback_guest'] = [
      '#type' => 'details',
      '#title' => $this->t('Feedback about booking'),
      'description' => [
        '#markup' => "<p>Email that is sent on the departure date and time; when the guest has left.</p>"
      ],
    ];
    $form['booking_feedback_guest']['booking_feedback_guest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('booking_feedback_guest_subject'),
    ];
    $form['booking_feedback_guest']['booking_feedback_guest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $config->get('booking_feedback_guest_body'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('abookings.bookingemailtemplates')
      ->set('provis_booking_guest_body', $form_state->getValue('provis_booking_guest_body'))
      ->set('provis_booking_guest_subject', $form_state->getValue('provis_booking_guest_subject'))

      ->set('deposit_reminder_guest_body', $form_state->getValue('deposit_reminder_guest_body'))
      ->set('deposit_reminder_guest_subject', $form_state->getValue('deposit_reminder_guest_subject'))

      ->set('expired_booking_guest_body', $form_state->getValue('expired_booking_guest_body'))
      ->set('expired_booking_guest_subject', $form_state->getValue('expired_booking_guest_subject'))

      ->set('confirmed_booking_guest_body', $form_state->getValue('confirmed_booking_guest_body'))
      ->set('confirmed_booking_guest_subject', $form_state->getValue('confirmed_booking_guest_subject'))

      ->set('booking_feedback_guest_body', $form_state->getValue('booking_feedback_guest_body'))
      ->set('booking_feedback_guest_subject', $form_state->getValue('booking_feedback_guest_subject'))

      ->set('prearrival_guest_body', $form_state->getValue('prearrival_guest_body'))
      ->set('prearrival_guest_subject', $form_state->getValue('prearrival_guest_subject'))
      ->save();
  }

}
