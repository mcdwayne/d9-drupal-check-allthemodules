<?php
/**
 * @file
 * Contains emails abookings.module.
 */

/**
 * CONTENTS
 * 
 * send_booking_email()
 * 
 * send_confirmation_email()
 * send_provis_booking_email()
 * send_prearrival_email()
 * send_reminder_email()
 * send_expired_booking_email()
 * send_completed_booking_email()
 */

use Drupal\node\Entity\Node;




function log_email_to_history(Node $booking, $description) {
  log_event_to_history($booking, 'email', $description, NULL);
}

/**
 * Saves a message about an email that was sent to the booking's history field.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param string $description
 *   What the email was about; eg. "Booking confirmation".
 * @param int $date
 *   (optional) Timestamp of when the email was sent. If not provided, defaults 
 *   to current date and time.
 */
function log_event_to_history(Node $booking, $type, $description, $date) {
  if ((! isset($date)) || ($date === NULL)) {
    $date = time();
  }

  $history = [];
  $field_history_val = $booking->get('field_history')->getValue();
  // kint($field_history_val, '$field_history_val');
  if ($field_history_val) {
    if (array_key_exists('value', $field_history_val[0])) {
      $history_json = $field_history_val[0]['value'];
      $history = json_decode($history_json);
      // kint($history, '$history');

      if (gettype($history) === 'object') {
        $history = [$history];
      }
    }
  }

  $history[] = [
    'date' => $date,
    'type' => $type,
    'description' => $description,
  ];

  $booking->set('field_history', json_encode($history));
  $booking->save();
}


/**
 * Gathers all booking email parameters, then sends an email.
 */
function send_booking_email($subject, $body, $to, $key) {
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');

  $mailManager        = \Drupal::service('plugin.manager.mail');
  $module             = 'abookings';
  $params = [
    'body'     => $body,
    'subject'  => $subject,
  ];
  $langcode           = \Drupal::currentUser()->getPreferredLangcode();
  $send               = true;
  $reply              = $notifn_emails_addresses_st;

  $result = $mailManager->mail(
    $module, $key, $to, $langcode, $params, $reply, $send);
  // kint($result, '$result');

  if ($result['result'] != true) {
    $message = t('There was a problem sending your email notification to @email, email key: @key.',
      array('@email' => $to, '@key' => $key));
    drupal_set_message($message, 'error');
    \Drupal::logger('mail-log')->error($message);
    return;
  }

  $message = t('An email notification has been sent to @email, email key: @key.',
    array('@email' => $to, '@key' => $key));
  drupal_set_message($message);
  \Drupal::logger('mail-log')->notice($message);
}



/**
 * Sends an email notifying guest that their booking has been placed, and is now provisional.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_provis_booking_email(Node $booking, Node $bookable) {

  // Send reminder email to guest

  $codes = get_all_codes_values($booking, $bookable);

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');

  $guest_email_subject = $templates_config->get('provis_booking_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('provis_booking_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $email_key = 'provisional_booking_guest';

  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $email_key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  // $recipients_string = \Drupal::config('abookings.bookingsettings')
  //   ->get('notifn_emails_addresses');
  // $recipients = explode(',', $recipients_string);

  // foreach ($recipients as $key => $address) {
  //   send_owner_email();
  // }

  log_email_to_history($booking, 'Provisional booking');
}



/**
 * Sends an email to remind guest to pay their booking deposit.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_reminder_email(Node $booking, Node $bookable) {

  // Send reminder email to guest

  $codes = get_all_codes_values($booking, $bookable);

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');

  $guest_email_subject = $templates_config->get('deposit_reminder_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('deposit_reminder_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $email_key = 'deposit_reminder_guest';

  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $email_key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  log_email_to_history($booking, 'Reminder to pay deposit');
}



/**
 * Sends an email notifying guest that their booking has expired because they 
 * did not pay the booking deposit.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_expired_booking_email(Node $booking, Node $bookable) {

  $codes = get_all_codes_values($booking, $bookable);

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');
  
  $guest_email_subject = $templates_config->get('expired_booking_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('expired_booking_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $email_key = 'expired_booking_guest';

  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $email_key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  log_email_to_history($booking, 'Booking expired');
}



/**
 * Sends an email notifying guest that their booking has been confirmed.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_confirmation_email(Node $booking, Node $bookable) {
  // kint($booking, '$booking');
  // kint($bookable, '$bookable');

  // Send reminder email to guest

  $codes = get_all_codes_values($booking, $bookable);

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');

  $guest_email_subject = $templates_config->get('confirmed_booking_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('confirmed_booking_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $email_key = 'confirmed_booking_guest';

  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $email_key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  // $notifn_emails_addresses = explode(',', $notifn_emails_addresses_st);
  // kint($notifn_emails_addresses, '$notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  log_email_to_history($booking, 'Booking confirmed');
}



/**
 * Sends an email to guest recommending that they participate on social media channels.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_prearrival_email(Node $booking, Node $bookable) {

  // Send reminder email to guest

  $codes = get_all_codes_values($booking, $bookable);

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');

  $guest_email_subject = $templates_config->get('prearrival_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('prearrival_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $email_key = 'prearrival_guest';

  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $email_key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  log_email_to_history($booking, 'Pre-arrival info');
}



/**
 * Sends an email asking the guest give their feedback on their stay.
 *
 * @param \Drupal\node\Node $booking
 *   The booking node that the email is regarding.
 * @param \Drupal\node\Node $bookable
 *   The bookable node that the booking is for.
 */
function send_completed_booking_email(Node $booking, Node $bookable) {
  // Send email to guest

  $templates_config = \Drupal::config('abookings.bookingemailtemplates');

  $codes = get_all_codes_values($booking, $bookable);

  $guest_email_subject = $templates_config->get('booking_feedback_guest_subject');
  $guest_email_subject = template_replace_codes($guest_email_subject, $codes);

  $guest_email_body = $templates_config->get('booking_feedback_guest_body');
  $guest_email_body = template_replace_codes($guest_email_body, $codes);

  $key = 'booking_feedback_guest';
  
  $address = $booking->get('field_email_address')->getValue()[0]['value'];
  send_booking_email($guest_email_subject, $guest_email_body, $address, $key);

  // Send a copy to owner's email addresses
  $notifn_emails_addresses_st = \Drupal::config('abookings.bookingsettings')
    ->get('notifn_emails_addresses');
  send_booking_email('COPY: ' . $guest_email_subject, $guest_email_body,
    $notifn_emails_addresses_st, $email_key);

  log_email_to_history($booking, 'Booking completed; feedback asked for.');
}