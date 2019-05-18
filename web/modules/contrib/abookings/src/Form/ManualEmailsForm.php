<?php

namespace Drupal\abookings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ManualEmailsForm.
 *
 * @package Drupal\abookings\Form
 */
class ManualEmailsForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manual_emails_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // kint($form, '$form');
    // kint($form_state, '$form_state');
    $booking = $form_state->getBuildInfo()['args'][0];
    // kint($booking, '$booking');
    $bookable_nid = $booking->get('field_bookable_unit')->getValue()[0]['target_id'];
    $bookable = node_load($bookable_nid);

    $codes = get_all_codes_values($booking, $bookable);

    $templates_config = \Drupal::config('abookings.bookingemailtemplates');


    // 1. Provisional booking placed

    $subject = $templates_config->get('provis_booking_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('provis_booking_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['provis_booking'] = [
      '#type' => 'details',
      '#title' => $this->t('Provisional booking placed'),
      'description' => [
        '#markup' => "<p>Email that is sent when a guest makes a booking.</p>"
      ],
    ];
    $form['provis_booking']['provis_booking_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['provis_booking']['provis_booking_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['provis_booking']['provis_booking_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Provisional Booking Email'),
    ];

    // 2. Reminder about deposit

    $subject = $templates_config->get('deposit_reminder_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('deposit_reminder_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['deposit_reminder'] = [
      '#type' => 'details',
      '#title' => $this->t('Reminder about deposit'),
      'description' => [
        '#markup' => "<p>Email that is sent to remind guests to pay the deposit.</p>"
      ],
    ];
    $form['deposit_reminder']['deposit_reminder_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['deposit_reminder']['deposit_reminder_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['deposit_reminder']['deposit_reminder_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Reminder about Deposit Email'),
    ];

    // 3. Booking expired
  
    // $subject = $templates_config->get('expired_booking_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('expired_booking_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['expired_booking'] = [
      '#type' => 'details',
      '#title' => $this->t('Booking expired'),
      'description' => [
        '#markup' => "<p>Email that is sent when the guest's booking has expired due to not paying the deposit.</p>"
      ],
    ];
    $form['expired_booking']['expired_booking_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['expired_booking']['expired_booking_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['expired_booking']['expired_booking_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Expired Booking Email'),
    ];

    // 4. Booking confirmed

    $subject = $templates_config->get('confirmed_booking_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('confirmed_booking_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['confirmed_booking'] = [
      '#type' => 'details',
      '#title' => $this->t('Booking confirmed'),
      'description' => [
        '#markup' => "<p>Email that is sent when a booking's status changes to 'confirmed'.</p>"
      ],
    ];
    $form['confirmed_booking']['confirmed_booking_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['confirmed_booking']['confirmed_booking_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['confirmed_booking']['confirmed_booking_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Confirmed Booking Email'),
    ];

    // 5. Pre-arrival

    $subject = $templates_config->get('prearrival_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('prearrival_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['prearrival_booking'] = [
      '#type' => 'details',
      '#title' => $this->t('Pre-arrival'),
      'description' => [
        '#markup' => "<p>Email that is sent just before the guest arrives.</p>"
      ],
    ];
    $form['prearrival_booking']['prearrival_booking_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['prearrival_booking']['prearrival_booking_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['prearrival_booking']['prearrival_booking_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Pre-arrival Email'),
    ];

    // 6. Feedback about booking

    $subject = $templates_config->get('booking_feedback_guest_subject');
    $subject = template_replace_codes($subject, $codes);

    $body = $templates_config->get('booking_feedback_guest_body');
    $body = template_replace_codes($body, $codes);

    $form['booking_feedback'] = [
      '#type' => 'details',
      '#title' => $this->t('Feedback about booking'),
      'description' => [
        '#markup' => "<p>Email that is sent on the departure date and time; when the guest has left.</p>"
      ],
    ];
    $form['booking_feedback']['booking_feedback_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subject,
    ];
    $form['booking_feedback']['booking_feedback_body'] = [
      '#type' => 'textarea',
      // '#title' => $this->t('Body'),
      // '#description' => $this->t(''),
      '#default_value' => $body,
    ];
    $form['booking_feedback']['booking_feedback_send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Feedback Email'),
    ];

    // kint($form, '$form');
    // kint($form_state, '$form_state');

    return $form;
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
    // kint($form, '$form');
    // dpm($form_state, '$form_state');

    // // Display result.
    // foreach ($form_state->getValues() as $key => $value) {
    //   dpm($key . ': ' . $value);
    // }

    $body;
    switch ($form_state->getValues()['op']) {
      case 'Send Provisional Booking Email':
        $email_key = 'provisional_booking_guest';
        $subject = $form_state->getValues()['provis_booking_subject'];
        $body = $form_state->getValues()['provis_booking_body'];
        break;
      case 'Send Reminder about Deposit Email':
        $email_key = 'deposit_reminder_guest';
        $subject = $form_state->getValues()['deposit_reminder_subject'];
        $body = $form_state->getValues()['deposit_reminder_body'];
        break;
      case 'Send Expired Booking Email':
        $email_key = 'expired_booking_guest';
        $subject = $form_state->getValues()['expired_booking_subject'];
        $body = $form_state->getValues()['expired_booking_body'];
        break;
      case 'Send Confirmed Booking Email':
        $email_key = 'confirmed_booking_guest';
        $subject = $form_state->getValues()['confirmed_booking_subject'];
        $body = $form_state->getValues()['confirmed_booking_body'];
        break;
      case 'Send Pre-arrival Email':
        $email_key = 'prearrival_guest';
        $subject = $form_state->getValues()['prearrival_booking_subject'];
        $body = $form_state->getValues()['prearrival_booking_body'];
        break;
      case 'Send Feedback Email':
        $email_key = 'booking_feedback_guest';
        $subject = $form_state->getValues()['booking_feedback_subject'];
        $body = $form_state->getValues()['booking_feedback_body'];
        break;

      default:
        # code...
        break;
    }
    // dpm('subject: ' . $subject);
    // dpm('body: ' . $body);
    // dpm('email_key: ' . $email_key);

    $booking = $form_state->getBuildInfo()['args'][0];
    $address = $booking->get('field_email_address')->getValue()[0]['value'];
    send_booking_email($subject, $body, $address, $email_key);
  }

}
