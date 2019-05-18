<?php

namespace Drupal\Tests\inmail\Kernel;

/**
 * Provides common helper methods for Inmail testing.
 *
 * All classes using this trait should annotate @requires module past_db
 */
trait InmailTestHelperTrait {

  /**
   * Returns the content of a test message.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The contents of the file.
   */
  protected function getMessageFileContents($filename) {
    $path = __DIR__ . '/../../modules/inmail_test/eml/' . $filename;
    return file_get_contents($path);
  }

  /**
   * Returns the last event with a given machine name.
   *
   * @param string $machine_name
   *   The event machine name.
   *
   * @return \Drupal\past\PastEventInterface|null
   *   The past event or null if not found.
   */
  protected function getLastEventByMachinename($machine_name) {
    $event_id = $this->getLastEventIdByMachinename($machine_name);
    return \Drupal::entityTypeManager()->getStorage('past_event')->load($event_id);
  }

  /**
   * Returns last event ID.
   *
   * @param string $machine_name
   *   The event machine name.
   *
   * @return int|null
   *   The event id or null if not found.
   */
  protected function getLastEventIdByMachinename($machine_name) {
    return db_query_range('SELECT event_id FROM {past_event} WHERE machine_name = :machine_name ORDER BY event_id DESC', 0, 1, [':machine_name' => $machine_name])->fetchField();;
  }

}
