<?php
/**
 * @file
 * Functions that manage and render the history (edits, etc.) of bookings.
**/

/**
 * CONTENTS
 *
 * get_history_as_markup()
 * get_history()
 * search_history()
**/



function get_history_as_markup($booking) {
  // kint('get_history_as_markup()');
  $history = get_history($booking);
  if (! $history) {
    return NULL;
  }

  $markup =
      '<h3 class="entity-meta__title">History</h3>'
    . '<table>'
    . '  <tr><th>Date</th>'
    . '  <th>Type</th>'
    . '  <th>Description</th></tr>';

  foreach ($history as $entry) {
    if (property_exists($entry, 'date')) {
      // kint($entry->date,'$entry->date');

      if (preg_match('/\d{2,4}-\d{2}-\d{2}/', $entry->date)) {
        $markup .= '  <tr><td>' . $entry->date . '</td>';
      }
      else {
        $markup .= '  <tr><td>' . date('d M Y, g:ia', $entry->date) . '</td>';
      }
    }
    if (property_exists($entry,'type')) {
      $markup .= '<td>' . $entry->type . '</td>';
    }
    if (property_exists($entry,'description')) {
      $markup .= '<td>' . $entry->description . '</td></tr>';
    }
  }

  $markup .= '</table>';

  return $markup;
}



function get_history($booking) {
  $history = $booking->get('field_history')->getValue();
  if (! $history) {
    return NULL;
  }
  if (! array_key_exists('value', $history[0])) {
    return NULL;
  }
  $history_json = $history[0]['value'];

  $history = json_decode($history_json);
  // kint($history, '$history');

  if ((! $history) || ($history === NULL)) {
    return null;
  }
  // kint($history, '$history');
  if (getType($history) === 'object') {
    $history = [$history];
  }

  return $history;
}



function search_history($booking, $key) {
  // kint($key, '$key');
  $history_raw = $booking->get('field_history')->getValue();
  if (! $history_raw) {
    return NULL;
  }
  if (! array_key_exists('value', $history_raw[0])) {
    return NULL;
  }
  $history = $history_raw[0]['value'];
  // kint($history, '$history');
  return strpos($history, $key) > 0;
}
