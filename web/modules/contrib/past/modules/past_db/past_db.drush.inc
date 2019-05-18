<?php

/**
 * @file
 * Past DB commands for Drush.
 */

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\past\PastEventArgumentInterface;
use Drupal\past_db\Entity\PastEvent;

/**
 * Implements hook_drush_command().
 */
function past_db_drush_command() {
  $items['past-show'] = [
    'description' => 'Show past events.',
    'drupal dependencies' => ['past_db'],
    'arguments' => [
      'event_id' => 'Optional id of a past event to show in detail. If not provided, a listing of most recent 10 events will be displayed.',
    ],
    'options' => [
      // Filter.
      'count' => 'The number of messages to show. Defaults to 10.',
      'severity' => 'Restrict to messages of a given severity level.',
      'module' => 'Restrict to messages of a given module.',
      'machine_name' => 'Restrict to messages of a given machine name.',
      'tail' => 'Continuously show new past events until interrupted.',
      'sleep-delay' => 'To be used in conjunction with --tail. This is the number of seconds to wait between each poll to the database. Delay is 1 second by default.',
      // Presentation.
      'full' => 'Full table information about each message.',
      'full-filter' => 'Full table information about messages containing filter.',
      'detail' => 'Return extended information about each message.',
      'detail-filter' => 'Return extended information about messages containing filter.',
      'nested' => 'Display nested argument data using print_r().',
    ],
    'examples' => [
      'drush past-show' => 'Show a tablelisting of most recent 10 messages.',
      'drush past-show 64' => 'Show in detail message with id 64.',
      'drush past-show "cron run succesful"' => 'Show a listing of most recent 10 messages containing the string "cron run succesful".',
      'drush past-show --count=46' => 'Show a listing of most recent 46 messages.',
      'drush past-show --severity=notice' => 'Show a listing of most recent 10 messages with a severity of notice.',
      'drush past-show --module=php' => 'Show a listing of most recent 10 messages of module php.',
      'drush past-show --machine_name="page not found"' => 'Show a listing of most recent 10 messages of machine name "page not found".',
      'drush past-show --tail --full' => 'Show a table listing of most recent 10 messages with extended information about each one and continue showing messages as they are registered in the past log.',
      'drush past-show --tail --full-filter' => 'Show a table listing of most recent 10 messages with extended information about each containing "watchdog_args" items and continue showing messages as they are registered in the past log.',
      'drush past-show --tail --detail' => 'Show most recent 10 messages with detail information about each one and continue showing messages as they are registered in the past log.',
      'drush past-show --tail --detail-filter="watchdog_args"' => 'Show a listing of most recent 10 messages with extended information about each containing "watchdog_args" items and continue showing messages as they are registered in the past log.',
      'drush past-show --tail --sleep-delay=2' => 'Do a tail of the past log with a delay of two seconds between each poll to the database.',
    ],
    'aliases' => ['ps'],
  ];
  return $items;
}

/**
 * Command callback.
 *
 * @param mixed $arg
 *   The id of the message to show or string to filter the message's text by.
 */
function drush_past_db_past_show($arg = NULL) {
  if (is_numeric($arg)) {
    past_db_drush_show_one($arg);
  }
  else {
    past_db_drush_show_many($arg);
  }
}

/**
 * Print a watchdog message.
 *
 * Display message in ONE form.
 * Key: Value
 * Keys are ucfirst machine keys with removed underscores.
 *
 * @param int $event_id
 *   The id of the message to show.
 */
function past_db_drush_show_one($event_id) {
  /** @var PastEvent $event */
  $event = \Drupal::entityTypeManager()->getStorage('past_event')->load($event_id);
  if (!$event) {
    return drush_set_error(dt('Past event #!event_id not found.', ['!event_id' => $event_id]));
  }

  // Preparing the event data.
  $nested = drush_get_option('nested', FALSE);
  $result = past_db_event_format_result($event, TRUE, $nested);

  // Preparing the ONE representation.
  $upper = new stdClass();
  foreach ($result as $key => $value) {
    $uc = ucfirst(str_replace('_', ' ', $key));
    if (is_array($value)) {
      $value = implode("\n", $value);
    }
    $upper->$uc = $value;
  }

  // Yay, display!
  drush_print_table(drush_key_value_to_array_table(json_decode(json_encode($upper), TRUE)));
  print "\n";
}

/**
 * Print a table of watchdog messages.
 *
 * @param string $filter
 *   String to filter the message's text by.
 */
function past_db_drush_show_many($filter = NULL) {
  $count = drush_get_option('count', 10);
  $module = drush_get_option('module');
  $machine_name = drush_get_option('machine_name');
  $severity = drush_get_option('severity');
  $tail = drush_get_option('tail', FALSE);
  $full = drush_get_option('full', FALSE);
  $detail = drush_get_option('detail', FALSE);
  $nested = drush_get_option('nested', FALSE);
  $display = $full ? 'full' : 'normal';
  if ($detail) {
    $display = 'detail';
  }
  $detail_filter = drush_get_option('detail-filter', NULL);
  $full_filter = drush_get_option('full-filter', NULL);

  // Prepare table data.
  $none = TRUE;
  $tbl = new Console_Table(CONSOLE_TABLE_ALIGN_LEFT, '');

  drush_log(dt('Most recent !count past event messages:', ['!count' => $count]));

  // Prepare header.
  $header = NULL;
  if ($display != 'normal') {
    $header = [
      dt('Id'), dt('Date'), dt('Severity'), dt('Module'),
      dt('Machine name'), dt('Message'),
    ];
    if ($display == 'full') {
      $header[] = dt('Arguments');
    }
    $tbl->setHeaders($header);
  }

  // We will reuse the table object to display each line generated while in
  // tail mode.
  // To make it possible some hacking is done on the object: remove the header
  // and reset the rows on each iteration.
  $last_event_id = 0;
  $last_format = NULL;
  // sleep-delay
  $sleep_delay = drush_get_option('sleep-delay', 1);

  while (TRUE) {
    $query = past_db_drush_query_events($filter, $severity, $module, $machine_name);
    // Apply a filter to exclude already printed events.
    if ($last_event_id) {
      $query->condition('event_id', $last_event_id, '>');
    }
    if (!$last_event_id) {
      // For the first tail query, limit the result.
      $query->range(0, $count);
    }
    $result = $query->execute();
    if ($result) {
      $none = FALSE;
      // Init this cycle.
      $table = [];
      // Reset table rows.
      $tbl->_data = [];
      /** @var \Drupal\Core\Entity\EntityInterface[] $events */
      $events = \Drupal::entityTypeManager()->getStorage('past_event')->loadMultiple(array_keys($result));
      // Process oldest first!
      $events = array_reverse($events);

      foreach ($events as $event) {
        /* @var PastEvent $event */
        $detail_line = $display == 'detail';
        $full_line = $display == 'full';
        // Check arguments if this line is a special case.
        $arguments = $event->getArguments();
        if ($detail_filter && ($arguments[$detail_filter])) {
          $detail_line = TRUE;
        }
        // Consider full-filter
        if ($full_filter && ($arguments[$full_filter])) {
          $full_line = TRUE;
        }
        // Prepare result, with arguments if requested.
        $arguments_line = $full_line || $detail_line;
        $result = past_db_event_format_result($event, $arguments_line, $nested);

        if ($detail_line) {
          // Preflush table.
          past_drush_table($tbl, $table, $header, $last_format);
          // Display single detail record.
          past_db_drush_show_one($event->id());
          $last_format = 'detail';
        }
        else {
          $row = [
            $result['event_id'], $result['date'], $result['severity'],
            $result['module'], $result['machine_name'], $result['message'],
          ];
          if ($full_line) {
            $argstr = '';
            foreach ($arguments as $key => $value) {
              $data = $value->getData();
              if (is_scalar($data)) {
                $argstr .= ucfirst($key) . ': ' . $data . "\n";
              }
              else {
                $argstr .= ucfirst($key) . ':' . "\n";
                $argstr .= $result->{'argument_' . $key} . "\n";
              }
            }
            $row[] = $argstr;
          }
          // enqueue.
          $table[] = $row;
        }
      }
      // Display table.
      past_drush_table($tbl, $table, $header, $last_format);
      // Terminate this loop.
      $last = array_pop($events);
      $last_event_id = $last->event_id->value;
    }
    if (!$tail) {
      print "\n";
      break;
    }
    // Only load 10 new events each time to avoid an overload.
    $count = 10;
    sleep($sleep_delay);
  }
  if ($none) {
    return drush_log(dt('No past events available.'), 'ok');
  }
}

/**
 * Update, print and flush table.
 */
function past_drush_table(&$tbl, &$table, $header, &$last_format) {
  if (!count($table)) {
    return;
  }
  // @todo for some reason the first header output is misaligned. fix!
  // @todo this is not possible with setHeaders(); fix drush!
  $tbl->_headers = NULL;
  if ($last_format == 'detail') {
    $tbl->setHeaders($header);
  }
  $tbl->addData($table);
  print $tbl->getTable();

  $last_format = 'table';
  $table = [];
}

/**
 * Format a past event entity.
 *
 * @param PastEvent $event
 *   Past event entity.
 * @param bool $show_arguments
 *   Return extended details.
 * @param bool $nested
 *   Consider nested data.
 *
 * @return array
 *   The result array with some attributes of the event.
 */
function past_db_event_format_result(PastEvent $event, $show_arguments = FALSE, $nested = FALSE) {
  $result = [];

  $result['event_id'] = $event->id();

  // Date.
  $result['date'] = \Drupal::service('date.formatter')->format($event->getTimestamp(), 'custom', 'd/M H:i');
  unset($result->timestamp);

  // Severity.
  $result['severity'] = past_db_get_severity_name($event->getSeverity());

  $result['module'] = $event->getModule();
  $result['machine_name'] = $event->getMachineName();

  // Message.
  $message_length = 188;

  if ($show_arguments) {
    // Print all the data available.
    foreach ($event->getArguments() as $key => $argument) {
      $result['argument_' . $key] = past_db_argument($argument, $nested);
    }
    $message_length = PHP_INT_MAX;
  }

  $result['message'] = Unicode::truncate(strip_tags(Html::decodeEntities($event->getMessage())), $message_length, FALSE, FALSE);
  return $result;
}

/**
 * Prepare argument for presentation.
 *
 * @param PastEventArgumentInterface $argument
 *   The argument to present.
 * @param bool $nested
 *   Whether non-scalar values should be presented in detail, rather than
 *   simply by its type.
 *
 * @return array mixed
 *   Return the argument data.
 */
function past_db_argument(PastEventArgumentInterface $argument, $nested) {
  $data = [];
  $argument_data = $argument->getData();
  if (is_array($argument_data) || is_object($argument_data)) {
    foreach ($argument->getData() as $name => $value) {
      if (!is_scalar($value) && $nested) {
        $value = print_r($value, TRUE);
      }
      $data[] = $name . ' => ' . $value;
    }
  }
  else {
    $data = $argument_data;
  }
  return $data;
}

/**
 * Build a EQF query based on given parameters.
 *
 * @param string $filter
 *   (optional) Filter to apply.
 * @param mixed $severity
 *   (optional) Int or String for a valid watchdog severity message.
 * @param string $module
 *   (optional) Value to filter watchdog messages by.
 * @param string $machine_name
 *   (optional) Machine name.
 *
 * @return \Drupal\Core\Entity\Query\QueryInterface
 *   The query object that can query the given entity type.
 */
function past_db_drush_query_events($filter = NULL, $severity = NULL, $module = NULL, $machine_name = NULL) {
  /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
  $query = \Drupal::entityQuery('past_event');
  if ($severity) {
    $query->condition('severity', past_db_get_severity_id($severity), '<=');
  }
  if ($filter) {
    $query->condition('message', $filter, 'CONTAINS');
  }
  if ($module) {
    $query->condition('module', $module);
  }
  if ($machine_name) {
    $query->condition('machine_name', $machine_name);
  }

  $query->sort('event_id', 'DESC');
  return $query;
}

/**
 * Get the identifier number of a given severity name.
 *
 * @param mixed $severity
 *   Int or String for a valid watchdog severity message.
 *
 * @return int
 *   The identifier number of the severity.
 */
function past_db_get_severity_id($severity) {
  if (is_numeric($severity)) {
    $severity_id = $severity;
  }
  else {
    $severities = RfcLogLevel::getLevels();
    $severity_id = array_search(strtolower($severity), array_map('strtolower', $severities));
  }
  return $severity_id;
}

/**
 * Get the severity name of a given severity id.
 *
 * @param mixed $severity
 *   Int or String for a valid watchdog severity message.
 *
 * @return string
 *   The name of the severity.
 */
function past_db_get_severity_name($severity) {
  $severities = RfcLogLevel::getLevels();
  if (isset($severities[$severity])) {
    return $severities[$severity];
  }
  return $severity;
}
