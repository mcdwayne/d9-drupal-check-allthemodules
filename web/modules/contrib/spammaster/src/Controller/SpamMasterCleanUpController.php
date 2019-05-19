<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterCleanUpController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function spammastercleanupkeys() {

    // Get variables.
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_license_status = $spammaster_settings->get('spammaster.license_status');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Set 90 days time.
      $time = date('Y-m-d H:i:s');
      $time_expires = date('Y-m-d H:i:s', strtotime($time . '-90 days'));

      // Delete data older than 3 months.
      db_query('DELETE FROM {spammaster_keys} WHERE date <= :time_expires', [':time_expires' => $time_expires]);

      // Log message.
      \Drupal::logger('spammaster-cleanup')->notice('Spam Master: weekly log cleanup successful run.');
      $spammaster_db_cleanup_insert = db_insert('spammaster_keys')->fields([
        'date' => $time,
        'spamkey' => 'spammaster-cleanup',
        'spamvalue' => 'Spam Master: weekly log cleanup successful run.',
      ])->execute();
    }
    else {
      // Log message.
      \Drupal::logger('spammaster-cleanup')->notice('Spam Master: weekly log cleanup did not run, check your license status.');
      $spammaster_db_cleanup_insert = db_insert('spammaster_keys')->fields([
        'date' => $time,
        'spamkey' => 'spammaster-cleanup',
        'spamvalue' => 'Spam Master: weekly log cleanup did not run, check your license status.',
      ])->execute();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function spammastercleanupbuffer() {

    // Get variables.
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_license_status = $spammaster_settings->get('spammaster.license_status');
    if ($spammaster_license_status == 'VALID' || $spammaster_license_status == 'MALFUNCTION_1' || $spammaster_license_status == 'MALFUNCTION_2') {
      // Set 90 days time.
      $time = date('Y-m-d H:i:s');
      $time_expires = date('Y-m-d H:i:s', strtotime($time . '-90 days'));

      // Delete data older than 3 months.
      db_query('DELETE FROM {spammaster_threats} WHERE date <= :time_expires', [':time_expires' => $time_expires]);

      // Log message.
      \Drupal::logger('spammaster-cleanup')->notice('Spam Master: weekly buffer cleanup successful run.');
      $spammaster_db_cleanup_insert = db_insert('spammaster_keys')->fields([
        'date' => $time,
        'spamkey' => 'spammaster-cleanup',
        'spamvalue' => 'Spam Master: weekly buffer cleanup successful run.',
      ])->execute();
    }
    else {
      // Log message.
      \Drupal::logger('spammaster-cleanup')->notice('Spam Master: weekly buffer cleanup did run, check your license status.');
      $spammaster_db_cleanup_insert = db_insert('spammaster_keys')->fields([
        'date' => $time,
        'spamkey' => 'spammaster-cleanup',
        'spamvalue' => 'Spam Master: weekly buffer cleanup did not run, check your license status.',
      ])->execute();
    }
  }

}
