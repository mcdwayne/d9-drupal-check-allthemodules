<?php

namespace Drupal\simple_analytics;

use Drupal\Core\Database\Database;

/**
 * Simple Analytics Actions.
 */
class SimpleAnalyticsActions {

  /**
   * Add to ststistic.
   *
   * @param array $data
   *   Preprocessed Visitors data.
   */
  public static function setStat(array $data) {

    if (!$data['LANGUAGE']) {
      $data['LANGUAGE'] = "und";
    }

    // Make signature.
    $data['SIGNATURE'] = md5($data['MOBILE'] . $data['SCREEN'] . $data['HTTP_USER_AGENT'] . $data['REMOTE_ADDR']);

    // Close action:
    $close = $data['CLOSE'];
    $id_last = 0;

    // Database connection.
    $con = Database::getConnection();

    $date_now = time();

    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
    $session = session_id();

    // Insert or update simple_analytics_visit.
    if (!$close) {
      $query = $con->merge('simple_analytics_visit')
        ->key(['SIGNATURE' => $data['SIGNATURE']])
        ->insertFields([
          'timestamp' => $date_now,
          'timeup' => $date_now,
          'counter' => 1,
          'session' => $session,
          'REFERER' => $data['REFERER'],
          'MOBILE' => $data['MOBILE'],
          'SCREEN' => $data['SCREEN'],
          'HTTP_USER_AGENT' => $data['HTTP_USER_AGENT'],
          'REMOTE_ADDR' => $data['REMOTE_ADDR'],
          'LANGUAGE' => $data['LANGUAGE'],
          'SIGNATURE' => $data['SIGNATURE'],
          'REQUEST_URI' => $data['REQUEST_URI'],
          'HTTP_HOST' => $data['HTTP_HOST'],
          'BOT' => $data['BOT'],
          'SERVEUR' => $data['SERVEUR'],
        ])
        ->updateFields([
          // Update time.
          'timeup' => $date_now,
        ])
        // Update counter.
        ->expression('counter', 'counter + :inc', [':inc' => 1]);
      $query->execute();
    }

    // Get latest record id.
    //
    if ($close) {
      $query = $con->select('simple_analytics_data', 'd');
      $query->fields('d', ['id']);
      $query->orderBy('id', "DESC");
      $query->range(0, 1);

      $result = $query->execute()->fetchAll();
      if (!empty($result[0]->id)) {
        $id_last = $result[0]->id;
      }
    }

    // Insert to data.
    if (!$close || !$id_last) {
      $con->insert('simple_analytics_data')
        ->fields([
          'timestamp' => $date_now,
          'session' => $session,
          'REQUEST_URI' => $data['REQUEST_URI'],
          'REFERER' => $data['REFERER'],
          'LANGUAGE' => $data['LANGUAGE'],
          'CLOSE' => 0,
          'SIGNATURE' => $data['SIGNATURE'],
        ])
        ->execute();
    }
    else {
      // Update on close action.
      $query = $con->update('simple_analytics_data')
        ->fields(['CLOSE' => $date_now])
        ->condition('id', $id_last);
      $query->execute();
    }

    // Dispatch tracking event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = new SimpleAnalyticsEvents($data);
    $dispatcher->dispatch(SimpleAnalyticsEvents::TRACK, $event);
  }

  /**
   * Archive yesterday datas and delete old datas.
   *
   * @return bool
   *   TRUE if Done, FALSE otherwise.
   */
  public static function archive() {

    // Database connection.
    $con = Database::getConnection();
    // Get config.
    $config = SimpleAnalyticsHelper::getConfig();
    $sa_tracker_duration = $config->get('sa_tracker_duration');
    if ($sa_tracker_duration < 7) {
      $sa_tracker_duration = 7;
    }

    // Date end.
    $date_end = strtotime(date("Y-m-d"));
    // Date Start.
    $date_stt = $date_end - 86400;

    $date_deletearchive = $date_stt - (86400 * $sa_tracker_duration);

    // Check is already archives.
    $query = $con->select('simple_analytics_archive', 'a');
    $query->fields('a', ['id', 'date']);
    $query->condition('date', $date_stt, '=');
    $result = $query->execute()->fetchAll();

    if (count($result)) {
      // Already archived.
      return FALSE;
    }
    //
    // Archive results.
    $query = $con->select('simple_analytics_data', 'd');
    $query->join('simple_analytics_visit', 'v', 'v.SIGNATURE = d.SIGNATURE');
    $query->fields('d', [
      'id',
      'timestamp',
      'LANGUAGE',
      'SIGNATURE',
    ]);
    $query->fields('v', [
      'HTTP_USER_AGENT',
      'MOBILE',
      'SCREEN',
      'REMOTE_ADDR',
      'BOT',
      'SIGNATURE',
    ]);
    $group = $query->andConditionGroup()
      ->condition('v.timestamp', $date_stt, '>')
      ->condition('v.timestamp', $date_end, '<');
    $query->condition($group);
    $result = $query->execute()->fetchAll();

    // Initialisation of the vars.
    $visits = 0;
    $page_view = 0;
    $hits = 0;
    $mobile = 0;
    $entry_direct = 0;
    $bots = 0;
    $bots_hits = 0;
    $settings_data = ['cookies' => 0, 'javascript' => 0];

    $signatures = [];

    foreach ($result as $row) {
      if (isset($signatures[$row->SIGNATURE])) {
        $signatures[$row->SIGNATURE]++;
        $is_new = FALSE;
      }
      else {
        $signatures[$row->SIGNATURE] = 1;
        $is_new = TRUE;
      }

      // Is a new user (user with same signature).
      if ($is_new) {

        if ($row->BOT) {
          $bots++;
        }
        else {
          $entry_direct += ($row->REFERER ? 0 : 1);
        }

        if ($row->MOBILE) {
          $mobile++;
        }
        else {
          $visits++;

          $settings_data['screens'][$row->SCREEN]++;
          $settings_data['languages'][$row->LANGUAGE]++;

          // Get data from browser USER_AGENT.
          $browser_data = SimpleAnalyticsHelper::getBrowser($row->HTTP_USER_AGENT, TRUE);
          if (!empty($browser_data)) {
            $version = $browser_data['browser'] . " " . $browser_data['majorver'];
            $settings_data['platform'][$browser_data['platform']]++;
            $settings_data['browser'][$browser_data['browser']]++;
            $settings_data['browser_version'][$version]++;
            $settings_data['cookies'] = $settings_data['cookies'] + $browser_data['cookies'];
            $settings_data['javascript'] = $settings_data['javascript'] + $browser_data['javascript'];
          }
          else {
            $settings_data['platform']['']++;
            $settings_data['browser']['']++;
          }
        }
      }

      // For every visit.
      if ($row->BOT) {
        $bots_hits++;
      }
      else {
        $page_view++;
      }
      // Count hits.
      $hits++;
    }

    $query = $con->insert('simple_analytics_archive');
    $query->fields([
      'date',
      'visits',
      'page_view',
      'hits',
      'mobile',
      'entry_direct',
      'bots',
      'bots_hits',
      'settings_data',
    ]);
    $query->values([
      $date_stt,
      $visits,
      $page_view,
      $hits,
      $mobile,
      $entry_direct,
      $bots,
      $bots_hits,
      json_encode($settings_data),
    ]);
    $query->execute();

    // Delete old results (data and visits).
    $query = $con->delete('simple_analytics_data');
    $query->condition('timestamp', $date_end, '<');
    $query->execute();
    $query = $con->delete('simple_analytics_visit');
    $query->condition('timestamp', $date_end, '<');
    $query->execute();

    $query = $con->delete('simple_analytics_archive');
    $query->condition('date', $date_deletearchive, '<');
    $result = $query->execute();

    return TRUE;
  }

}
