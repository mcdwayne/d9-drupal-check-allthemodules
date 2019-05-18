<?php
/**
 * @file
 * Contains functions that manage codes used in email templates.
 */

use Drupal\node\Entity\Node;

/**
 * CONTENTS
 *
 * get_codes_fm_settings()
 * get_codes_fm_bookable()
 * get_codes_fm_booking()
 * get_all_codes_values()
 * get_codes()
 * template_replace_codes()
 */


function get_all_codes_values($booking, $bookable) {
  $booking_codes = get_codes_fm_booking($booking);
  $bookable_codes = get_codes_fm_bookable($bookable);
  $settings_codes = get_codes_fm_settings();
  return array_merge($booking_codes, $bookable_codes, $settings_codes);
}


function get_codes_fm_settings() {
  $codes = [
    '{deposit_hours}'     => \Drupal::config('abookings.bookingsettings')->get('deposit_hours'),
    '{deposit_rate}'      => \Drupal::config('abookings.bookingsettings')->get('deposit_rate'),
  ];
  return $codes;
}


function get_codes_fm_bookable($bookable) {
  // kint($bookable, '$bookable');
  $codes = [
    '{bookable_id}'       => $bookable->id(),
    '{bookable_name}'     => $bookable->label(),
  ];

  $checkin_time = $bookable->get('field_checkin_time')->getValue();
  if ($checkin_time)
    $codes['{checkin_time}'] = $checkin_time[0]['value'];

  $checkout_time = $bookable->get('field_checkout_time')->getValue();
  if ($checkout_time)
    $codes['{checkout_time}'] = $checkout_time[0]['value'];

  $max_guests = $bookable->get('field_max_guests')->getValue();
  if ($max_guests)
    $codes['{max_guests}'] = $max_guests[0]['value'];

  $min_guests = $bookable->get('field_min_guests')->getValue();
  if ($min_guests)
    $codes['{min_guests}'] = $max_guests[0]['value'];

  return $codes;
}


function get_codes_fm_booking(Node $booking) {
  $base_cost = get_booking_costs($booking)['base_cost'];

  $promo_code_provided_val = $booking->get('field_promo_code_provided')->getValue();
  $promo_code_provided = in_array(0, $promo_code_provided_val) ? $promo_code_provided_val[0]['value'] : '';

  $deposit_rate = \Drupal::config('abookings.bookingsettings')->get('deposit_rate');
  $deposit = intval($base_cost) / (100 / $deposit_rate);

  $codes = [
    // field_addons - How to show this?
    '{booking_id}'          => $booking->id(),
    '{promo_code_provided}' => $promo_code_provided,
    '{deposit_amount}'      => $deposit,
    '{deposit_rate}'        => $deposit_rate . '%',
    '{base_cost}'           => $base_cost,
    '{total_cost}'          => get_booking_costs($booking)['total_cost'],
    '{total_payable}'       => get_booking_costs($booking)['total_payable'],
    // '{amount_due}'          => $booking->get('field_amount_due')->getValue()[0]['value'],
    '{amount_due}'          => get_booking_costs($booking)['amount_due'],
  ];
  // foreach ($codes as $code_text => $field_name) {
  //   set_code_if_exists($codes, $code_text, $booking, $field_name, 'value');
  // }

  set_code_if_exists($codes, 'checkin_date', $booking, 'field_checkin_date', 'value');
  set_code_if_exists($codes, 'checkout_date', $booking, 'field_checkout_date', 'value');
  set_code_if_exists($codes, 'booking_status', $booking, 'field_booking_status', 'value');
  set_code_if_exists($codes, 'first_name', $booking, 'field_first_name', 'value');
  set_code_if_exists($codes, 'last_name', $booking, 'field_last_name', 'value');
  set_code_if_exists($codes, 'email_address', $booking, 'field_email_address', 'value');
  set_code_if_exists($codes, 'num_nights', $booking, 'field_num_nights', 'value');
  set_code_if_exists($codes, 'num_guests', $booking, 'field_num_guests', 'value');
  set_code_if_exists($codes, 'country', $booking, 'field_country', 'value');
  set_code_if_exists($codes, 'notes', $booking, 'field_notes', 'value');
  set_code_if_exists($codes, 'phone_number', $booking, 'field_phone_number', 'value');
  set_code_if_exists($codes, 'phone_number_alt', $booking, 'field_phone_number_alt', 'value');
  return $codes;
}

/**
 * For a given field, if the field has a value, set it's template code to it.
 *
 * @param $value_key
 *   'value' for most fields, otherwise 'target_id' if a reference field.
 */
function set_code_if_exists(&$codes, $code_text, $entity, $field_name, $value_key) {
  $codes['{' . $code_text . '}'] = '—' . $code_text . ' not provided—';
  $field_value = $entity->get($field_name)->getValue();
  if (! array_key_exists(0, $field_value)) {
    return NULL;
  }
  if (! array_key_exists($value_key, $field_value[0])) {
    return NULL;
  }
  // return $field_value[0][$value_key];
  $codes['{' . $code_text . '}'] = $field_value[0][$value_key];
}



function get_codes() {
  $codes = [
    'settings' => [
      '{deposit_hours}'     => '',
      '{deposit_rate}'      => '',
    ],
    'bookable' => [
      // field_addons
      // field_price_type
      '{bookable_id}'       => '',
      '{bookable_name}'     => '',
      '{checkin_time}'      => '',
      '{checkout_time}'     => '',
      '{max_guests}'        => '',
      '{min_guests}'        => '',
    ],
    'booking' => [
      // field_addons
      // field_history
      '{booking_id}'        => '',
      '{checkin_date}'      => '',
      '{checkout_date}'     => '',
      '{booking_status}'    => '',
      '{first_name}'        => '',
      '{last_name}'         => '',
      '{booking_status}'    => '',
      '{country}'           => '',
      '{email_address}'     => '',
      '{first_name}'        => '',
      '{notes}'             => '',
      '{num_guests}'        => '',
      '{num_nights}'        => '',
      '{phone_number}'      => '',
      '{phone_number_alt}'  => '',
      '{promo_code_provided}' => '',
      '{deposit_amount}'    => '',
      '{deposit_rate}'      => '',
      '{base_cost}'         => '',
      '{total_cost}'        => '',
      '{total_payable}'     => '',
      '{amount_due}'        => '',
    ],
  ];
  return $codes;
}



function template_replace_codes($template, $codes) {

  foreach ($codes as $search => $replace) {
    $template = str_replace($search, $replace, $template);
  }
  return $template;
}
