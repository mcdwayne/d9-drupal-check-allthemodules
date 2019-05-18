<?php
/**
 * @file
 * Contains functions that query site data such as fetching bookings.
**/

/**
 * CONTENTS
 *
 * get_bookables()
 * get_bookings()
 * get_promo()
 * get_seasons_data()
 * get_bookings_data()
**/




/**
 * Runs a query to fetch all bookable units.
 *
 * @return array
 *   An array in the format: ['nid' => node].
 */
function get_bookables() {
  $bookables = [];

  $query = \Drupal::entityQuery('node');
  $results = $query
    ->condition('status', 1)
    ->condition('type', 'bookable_unit')
    // ->range(0, 1) // Just for development
    ->execute();
  // kint($results, '$results');

  // Save the array in the format: ['nid' => node].
  foreach ($results as $rid => $nid) {
    $bookables[$nid] = node_load($nid);
  }
  // kint($bookables, '$bookables');

  return $bookables;
}



/**
 * Runs a query to fetch all bookings that have a specific booking status.
 *
 * @param string $status
 *   The value of the booking status the bookings must have.
 *
 * @return array
 *   An array of results in the format: ['rid' => nid], where rid is the booking's revision id.
 */
function get_bookings($status) {
  // $langcode = \Drupal::currentUser()->getPreferredLangcode();
  $query = \Drupal::entityQuery('node');
  $results = $query
    ->condition('status', 1)
    ->condition('type', 'booking')
    // ->range(0, 1) // Just for development
    ->condition('field_booking_status.value', $status, '=')
    ->execute();
  // kint($results, '$results');
  return $results;
}



/**
 * Runs a query to fetch a promotion with a specific promo code. Returns the first matched promotion.
 *
 * @param string $code_provided
 *   A string of the value of the promo code that the promotions must have.
 *
 * @return \Drupal\node\Entity\Node
 *   The first promotion node found matching the promo code provided.
 */
function get_promo($code_provided, $checkin_date, $nights) {
  // kint($code_provided, '$code_provided');

  // Fetch all promos
  $promo;

  $query = \Drupal::entityQuery('node');
  $results = $query
    ->condition('status', 1)
    ->condition('type', 'promo')
    // ->range(0, 1) // Just for development
    ->execute();
  // kint($results, '$results');

  // Loop through them
  foreach ($results as $rid => $nid) {
    $promo = node_load($nid);
    // kint($promo, '$promo');

    // Check if code provided matches this promo's code
    $code = $promo->get('field_promo_code')->getValue()[0]['value'];
    // kint($code, '$code');
    if ($code_provided !== $code) {
      continue;
    }

    // Check that booking is within matched promo's valid dates.
    $start_date = $promo->get('field_start_date')->getValue()[0]['value'];
    $end_date = $promo->get('field_end_date')->getValue()[0]['value'];
    if (! ($checkin_date >= $start_date && $checkin_date <= $end_date)) {
      throw new BadRequestHttpException("Matched code, but not valid checkin date");
    }

    // Check that booking is for at least 'min nights'.
    $min_nights = $promo->get('field_min_nights')->getValue()[0]['value'];

    if (! (intval($nights) >= intval($min_nights))) {
      throw new BadRequestHttpException("Matched code, but too few nights.");
    }

    return $promo;
  }
  if (! $promo) {
    throw new BadRequestHttpException("Promo code doesn't exist.");
  }
  return NULL;
}



/**
 * Gets data of all seasons. Uses 'data_seasons' view.
 */
function get_seasons_data() {

  $seasons_renderable = [];
  $seasons_data = views_get_view_result('data_seasons');
  // kint($seasons_data, '$seasons_data');

  foreach ($seasons_data as $season_data) {
    $season = $season_data->_entity;

    // Get Start date as unix timestamp
    $start_iso_8601_date = $season->get('field_start_date')->get(0)
      ->getValue()['value'];
    $start_timestamp = \Drupal\Core\Datetime\DrupalDateTime
      ::createFromFormat('Y-m-d', $start_iso_8601_date)->format('U'); // 'c'

    // Get End date as unix timestamp
    $end_iso_8601_date = $season->get('field_end_date')->get(0)
      ->getValue()['value'];
    $end_timestamp = \Drupal\Core\Datetime\DrupalDateTime
      ::createFromFormat('Y-m-d', $end_iso_8601_date)->format('U'); // 'c'

    // Add season data to drupalSettings (for JS to use)
    $seasons_renderable[$season->id()] = [
      'field_start_date' => $start_timestamp,
      'field_end_date' => $end_timestamp,
      'field_seasonal_price' => $season->get('field_seasonal_price')->getValue()[0]['value'],
    ];
  }
  // kint($content['#attached']['drupalSettings']['seasons'], 'seasons_data settings');
  return $seasons_renderable;
}



/**
 * Gets data of all bookings. Uses 'data_bookings' view.
 */
function get_bookings_data() {

  $bookings_renderable = [];
  $bookings_data = views_get_view_result('data_bookings');
  // kint($seasons_data, '$seasons_data');

  foreach ($bookings_data as $booking_data) {
    $booking = $booking_data->_entity;

    // Get Check-in date as unix timestamp
    $checkin_iso_8601_date = $booking->get('field_checkin_date')->get(0)
      ->getValue()['value'];
    $checkin_timestamp = \Drupal\Core\Datetime\DrupalDateTime
      ::createFromFormat('Y-m-d', $checkin_iso_8601_date)->format('U'); // 'c'

    // Get Check-out date as unix timestamp
    $checkout_iso_8601_date = $booking->get('field_checkout_date')->get(0)
      ->getValue()['value'];
    $checkout_timestamp = \Drupal\Core\Datetime\DrupalDateTime
      ::createFromFormat('Y-m-d', $checkout_iso_8601_date)->format('U'); // 'c'

    // Add booking data to drupalSettings (for JS to use)
    $bookings_renderable[$booking->id()] = [
      'field_checkin_date' => $checkin_timestamp,
      'field_checkout_date' => $checkout_timestamp,
      'field_booking_status' => $booking->get('field_booking_status')->getValue()[0]['value'],
    ];
  }
  // kint($content['#attached']['drupalSettings']['seasons'], 'seasons_data settings');
  return $bookings_renderable;
}
