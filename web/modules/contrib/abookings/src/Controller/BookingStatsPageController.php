<?php

namespace Drupal\abookings\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class BookingStatsPageController.
 *
 * @package Drupal\abookings\Controller
 */
class BookingStatsPageController extends ControllerBase {

  /**
   * Displaypage.
   *
   * @return string
   *   Return Hello string.
   */
  public function displayPage() {

    $bookable_nid = \Drupal::request()->get('bookable');
    // kint($bookable_nid, '$bookable_nid');

    $content = [
      '#type' => 'markup',
      'bookable_filter' => [
        '#type' => 'select',
        '#options' => [
          '' => 'ALL'
        ],
        // '#empty_option' => 'ALL',
        // '#empty_value' => 'test',
        // '#default_value' => $bookable_nid,
        '#title' => $this->t('Bookable'),
        // '#description' => $this->t(''),
        '#attributes' => [
          'name' => 'bookable_filter',
        ],
      ],
      'charts' => [
        '#markup' => '<div id="bookingCharts"></div>',
      ],
      '#attached' => array(
        'library' => array(
          'abookings/chartJS',
        ),
      ),
    ];

    $bookables = get_bookables();
    // kint($bookables, '$bookables');
    foreach ($bookables as $nid => $bookable) {
      $content['bookable_filter']['#options'][$nid] = $bookable->getTitle();
    }

    $content['#attached']['drupalSettings'] = [
      'booking_data' => [],
    ];

    $content['page']['#attached']['drupalSettings']['booking_data']
      = get_booking_data($bookable_nid);
    // $data = get_booking_data();
    // kint($data, '$data');


    // kint($content, '$content');
    return $content;
  }

}


function get_booking_data($bookable_nid) {

  $current_timestamp = time();
  // $timezone = drupal_get_user_timezone();
  // kint($timezone, '$timezone');
  $datetime = \Drupal\Core\Datetime\DrupalDateTime
    ::createFromFormat('U', $current_timestamp);
  // drupal_set_message(t('1'), 'status', FALSE);

  // Change to first day of month
  $d = intval($datetime->format('d'));
  $m = intval($datetime->format('m'));
  $Y = intval($datetime->format('Y'));
  $datetime->setDate($Y , $m + 1, 1);

  // Build the array of dates to query for
  $num_months = $message['from'] = \Drupal::config('abookings.bookingsettings')->get('settings_months');
  $date_ranges = [];
  for ($i=0; $i < $num_months; $i++) {
    $key = $datetime->format('M Y');
    // $key = $datetime->format('d M Y');
    $date_ranges[$key] = [$datetime->format('Y-m-d')];
    $datetime->modify('-1 month');
    $date_ranges[$key][] = $datetime->format('Y-m-d');
  }
  $date_ranges = array_reverse($date_ranges);
  // kint($date_ranges, '$date_ranges');

  $bookable = NULL;

  $data = [
    'bookings_count'  => [
      'data'         => stats_get_bookings_count($date_ranges, $bookable_nid),
      'title'        => 'Bookings per month',
      'series_label' => '# bookings',
      'colour'       => 'orange',
    ],
    'occupancy_perc' => [
      'data'         => stats_get_occupancy_perc($date_ranges, $bookable_nid),
      'title'        => 'Occupancy per month',
      'series_label' => '% occupancy',
      'colour'       => 'yellow',
    ],
    'revenue'        => [
      'data'         => stats_get_revenue($date_ranges, $bookable_nid),
      'title'        => 'Revenue per month',
      'series_label' => 'revenue (R)',
      'colour'       => 'light_green',
    ],
  ];
  return $data;
}



function stats_get_bookings_count($date_ranges, $bookable_nid = NULL) {
  $data = [];

  foreach ($date_ranges as $key => $dates_pair) {
    // For each pair, the later date will be first
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'booking')
      ->condition('field_booking_status.value', 'completed', '=')
      ->condition('field_checkin_date.value', $dates_pair[1], '>=')
      ->condition('field_checkin_date.value', $dates_pair[0], '<')
      ->count()
      // ->range(0, 1) // Just for development
    ;

    if ($bookable_nid) {
      $query->condition('field_bookable_unit.target_id', $bookable_nid, '=');
    }

    $result = $query->execute();
    // kint($result, '$result');

    $data[$key] = intval($result);
  }
  return $data;
}



function stats_get_occupancy_perc($date_ranges, $bookable_nid = NULL) {
  // kint($date_ranges, '$date_ranges');
  $data = [];

  // Loop through date range pairs (eg. months)
  foreach ($date_ranges as $key => $dates_pair) {
    // For each pair, the later date will be first
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'booking')
      ->condition('field_booking_status.value', 'completed', '=')
      ->condition('field_checkin_date.value', $dates_pair[1], '>=')
      ->condition('field_checkin_date.value', $dates_pair[0], '<')
      // ->range(0, 1) // Just for development
    ;

    if ($bookable_nid) {
      $query->condition('field_bookable_unit.target_id', $bookable_nid, '=');
    }

    $results = $query->execute();

    $nodes = [];
    foreach ($results as $rid => $nid) {
      $booking = node_load($nid);
      $nodes[$nid] = [
        'checkin_date'  => $booking->get('field_checkin_date')->getValue()[0]['value'],
        'checkout_date' => $booking->get('field_checkout_date')->getValue()[0]['value'],
      ];
    }
    // kint($nodes, '$nodes');

    $i_date = \Drupal\Core\Datetime\DrupalDateTime
      ::createFromFormat('Y-m-d', $dates_pair[1]);
    $start_month = intval($i_date->format('n'));
    $start_year = intval($i_date->format('Y'));
    // $days_in_month = \cal_days_in_month(CAL_GREGORIAN, $start_month , $start_year);
    $days_in_month = date('t', mktime(0, 0, 0, $start_month, 1, $start_year)); 
    // kint($days_in_month, '$days_in_month');
    $days_occupied = 0;

    for ($i=0; $i < $days_in_month; $i++) {

      foreach ($nodes as $nid => $node_dates) {
        $i_date_Ymd = $i_date->format('Y-m-d');

        if (($node_dates['checkin_date'] <= $i_date_Ymd)
          && ($node_dates['checkout_date'] >= $i_date_Ymd)) {
            $days_occupied ++;
            continue; // Skip the other nodes on for this date
        }
      }
      $i_date->modify('+1 days');
    }
    // kint($days_occupied, '$days_occupied');

    // Save month's occupancy
    $data[$key] = round($days_occupied / $days_in_month * 100, 1);
  }
  // kint($data, '$data');
  return $data;
}



function stats_get_revenue($date_ranges, $bookable_nid = NULL) {
  $data = [];

  foreach ($date_ranges as $key => $dates_pair) {
    // For each pair, the later date will be first
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'booking')
      ->condition('field_booking_status.value', 'completed', '=')
      ->condition('field_checkin_date.value', $dates_pair[1], '>=')
      ->condition('field_checkin_date.value', $dates_pair[0], '<')
      // ->range(0, 1) // Just for development
    ;

    if ($bookable_nid) {
      $query->condition('field_bookable_unit.target_id', $bookable_nid, '=');
    }

    $results = $query->execute();

    $total = 0;
    foreach ($results as $rid => $nid) {
      $booking = node_load($nid);
      $booking_base_cost = $booking->get('field_base_cost')->getValue();
      if (array_key_exists(0, $booking_base_cost)) {
        $total += floatval($booking_base_cost[0]['value']);
      }
    }
    // kint($total, '$total');

    $data[$key] = intval($total);
  }
  return $data;
}
