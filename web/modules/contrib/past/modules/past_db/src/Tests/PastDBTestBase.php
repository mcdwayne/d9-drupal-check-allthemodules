<?php

namespace Drupal\past_db\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\past_db\Entity\PastEvent;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for Past DB tests.
 */
abstract class PastDBTestBase extends WebTestBase {

  protected $event_desc;
  protected $machine_name;
  protected $severities;
  protected $events = [];

  /**
   * Creates some sample events.
   */
  protected function createEvents($count = 99) {
    // Set some for log creation.
    $this->machine_name = 'machine name';
    $this->severities = RfcLogLevel::getLevels();
    $severities_codes = array_keys($this->severities);
    $severities_count = count($this->severities);
    $this->event_desc = 'message #';

    // Prepare some logs.
    for ($i = 0; $i <= $count; $i++) {
      $event = past_event_create('past_db', $this->machine_name, $this->event_desc . ($i + 1));
      $event->setReferer('http://example.com/test-referer');
      $event->setLocation('http://example.com/this-url-gets-heavy-long/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttest/seeme.htm');
      $event->addArgument('arg1', 'First Argument');
      $event->addArgument('arg2', new \stdClass());
      $event->addArgument('arg3', FALSE);
      $event->setSeverity($severities_codes[$i % $severities_count]);
      $event->save();
      $this->events[$event->id()] = $event;
    }
  }

  /**
   * Loads created sample events.
   *
   * @return PastEvent[]
   *   The created sample events.
   */
  protected function loadEvents() {
    return \Drupal::entityTypeManager()
      ->getStorage('past_event')
      ->loadMultiple();
  }

  /**
   * Loads a created sample event using the id.
   *
   * @return PastEvent
   *   One created sample event.
   */
  protected function loadEvent($id) {
    return \Drupal::entityTypeManager()
      ->getStorage('past_event')
      ->load($id);
  }
}
