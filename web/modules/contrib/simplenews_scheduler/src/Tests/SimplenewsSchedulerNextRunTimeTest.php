<?php

/**
 * @file
 * Simplenews scheduler next run time test functions.
 *
 * @ingroup simplenews_scheduler
 */

namespace Drupal\simplenews_scheduler\Tests;

/**
 * Testing next run times for newsletters monthly.
 *
 * @group simplenews_scheduler
 */
class SimplenewsSchedulerNextRunTimeTest extends SimplenewsSchedulerWebTestBase {

  /**
   * Test a frequency of 1 month.
   */
  function testNextRunTimeOneMonth() {
    // The start date of the edition.
    $this->edition_day = '05';
    $start_date = new \DateTime("2012-01-{$this->edition_day} 12:00:00");

    // Fake newsletter parent data: sets the interval, start date, and frequency.
    $newsletter_parent_data = (object) array(
      'nid' => 1,
      'last_run' => 0,
      'activated' => '1',
      'send_interval' => 'month',
      'interval_frequency' => '1',
      'start_date' => $start_date->getTimestamp(),
      'next_run' => $start_date->getTimestamp(), // Needs to be set manually when creating new records programmatically.
      'stop_type' => '0',
      'stop_date' => '0',
      'stop_edition' => '0',
      'php_eval' => '',
      'title' => '[node:title] for [current-date:long]',
    );

    // Number of days to run for.
    $days = 370;
    // Index of the days we've done so far.
    $added_days = 0;
    // Iterate over days.
    $last_run_time = $start_date->getTimestamp();
    while ($added_days <= $days) {
      // Create today's date at noon and get the timestamp.
      $date = clone($start_date);
      $date->add(new \DateInterval("P{$added_days}D"));
      $timestamp_noon = $date->getTimestamp();

      // Get the next run time from the API function we're testing.
      $next_run_time = simplenews_scheduler_calculate_next_run_time($newsletter_parent_data, $timestamp_noon);
      //debug($edition_time);

      if ($next_run_time != $last_run_time) {
        $offset = _simplenews_scheduler_make_time_offset($newsletter_parent_data->send_interval, $newsletter_parent_data->interval_frequency);

        $next_run_date = date_add(date_create(date('Y-m-d H:i:s', $last_run_time)), date_interval_create_from_date_string($offset));
        $this->assertEqual($next_run_date->getTimestamp(), $next_run_time, t('New next run timestamp has advanced by the expected offset of 2offset.', array(
          '@offset' => $offset,
        )));

        $last_run_time = $next_run_time;
      }

      $this->assertTrue($timestamp_noon < $next_run_time, t('Next run time of @next-run is in the future relative to current time of @now.', array(
        '@next-run' => date("Y-n-d H:i:s", $next_run_time),
        '@now'      => date("Y-n-d H:i:s", $timestamp_noon),
      )));

      $interval = $newsletter_parent_data->interval_frequency * 31 * 24 * 60 * 60;
      //$this->assertTrue($next_run_time - $timestamp_noon <= $interval, t('Next run timestamp is less than or exactly one month in the future.'));

      // Create a date object from the timestamp. The '@' makes the constructor
      // consider the string as a timestamp.
      $next_run_date = new \DateTime(date('Y-m-d H:i:s', $last_run_time));
      $d = date_format($next_run_date, 'd');
      $this->assertEqual($next_run_date->format('d'), $this->edition_day, t('Next run timestamp is on same day of the month as the start date.'));
      $this->assertEqual($next_run_date->format('H:i:s'), '12:00:00', t('Next run timestamp is at the same time.'));

      $added_days++;
    } // while days
  }

  /**
   * Test a frequency of 2 months.
   */
  function testNextRunTimeTwoMonths() {
    // The start date of the edition.
    $this->edition_day = '05';
    $start_date = new \DateTime("2012-01-{$this->edition_day} 12:00:00");

    // Fake newsletter parent data: sets the interval, start date, and frequency.
    $newsletter_parent_data = (object) array(
      'nid' => 1,
      'last_run' => 0,
      'activated' => '1',
      'send_interval' => 'month',
      'interval_frequency' => '2',
      'start_date' => $start_date->getTimestamp(),
      'next_run' => $start_date->getTimestamp(), // Needs to be set manually when creating new records programmatically.
      'stop_type' => '0',
      'stop_date' => '0',
      'stop_edition' => '0',
      'php_eval' => '',
      'title' => '[node:title] for [current-date:long]',
    );

    // Number of days to run for.
    $days = 370;
    // Index of the days we've done so far.
    $added_days = 0;
    // Iterate over days.
    while ($added_days <= $days) {
      // Create today's date at noon and get the timestamp.
      $date = clone($start_date);
      $date->add(new \DateInterval("P{$added_days}D"));
      $timestamp_noon = $date->getTimestamp();

      // Get the next run time from the API function we're testing.
      $next_run_time = simplenews_scheduler_calculate_next_run_time($newsletter_parent_data, $timestamp_noon);
      //debug($edition_time);

      $this->assertTrue($timestamp_noon < $next_run_time, t('Next run time of @next-run is in the future relative to current time of @now.', array(
        '@next-run' => date("Y-n-d H:i:s", $next_run_time),
        '@now'      => date("Y-n-d H:i:s", $timestamp_noon),
      )));

      $interval = $newsletter_parent_data->interval_frequency * 31 * 24 * 60 * 60;
      $this->assertTrue($next_run_time - $timestamp_noon <= $interval, t('Next run timestamp is less than or exactly two months in the future.'));

      // Create a date object from the timestamp. The '@' makes the constructor
      // consider the string as a timestamp.
      $next_run_date = new \DateTime("@$next_run_time");
      $d = date_format($next_run_date, 'd');
      $this->assertEqual($next_run_date->format('d'), $this->edition_day, t('Next run timestamp is on same day of the month as the start date.'));

      $added_days++;
    } // while days
  }

}
