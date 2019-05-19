<?php

/**
 * @file
 * Simplenews scheduler edition time test functions.
 *
 * @ingroup simplenews_scheduler
 */

namespace Drupal\simplenews_scheduler\Tests;

/**
 * Testing edition times for newsletters due every month and every 2 months.
 *
 * @group simplenews_scheduler
 */
class SimplenewsSchedulerEditionTimeTest extends SimplenewsSchedulerWebTestBase {

  /**
   * Test a frequency of 1 month.
   */
  function testEditionTimeOneMonth() {
    // The start date of the edition.
    $this->edition_day = '01';
    $start_date = new \DateTime("2012-01-{$this->edition_day} 12:00:00");

    // Fake newsletter parent data: sets the interval, start date, and frequency.
    $newsletter_parent_data = (object) array(
      'nid' => 1,
      'last_run' => 0,
      'activated' => '1',
      'send_interval' => 'month',
      'interval_frequency' => '1',
      'start_date' => $start_date->getTimestamp(),
      'stop_type' => '0',
      'stop_date' => '0',
      'stop_edition' => '0',
      'php_eval' => '',
      'title' => '[node:title] for [current-date:long]',
    );

    // Number of days to run for. Go just over one year.
    $days = 370;
    // Index of the days we've done so far.
    $added_days = 0;
    // Iterate over days.
    while ($added_days <= $days) {
      // Create today's date at noon and get the timestamp.
      $date = clone($start_date);
      $date->add(new \DateInterval("P{$added_days}D"));
      $timestamp_noon = $date->getTimestamp();

      $edition_time = simplenews_scheduler_calculate_edition_time($newsletter_parent_data, $timestamp_noon);
      //debug($edition_time);

      // Expected edition time is always the {$this->edition_day}th of the month
      // at noon.
      $edition_time_formatted = date("Y-m-d H:i:s", $edition_time);
      $this_month = $date->format('Y-m');
      $expected_time_formatted = "{$this_month}-{$this->edition_day} 12:00:00";

      $this->assertEqual($edition_time_formatted, $expected_time_formatted, t("Edition time of @edition-time matches expected time of @edition-time-expected at time 2now.", array(
        '@edition-time' => $edition_time_formatted,
        '@edition-time-expected' => $expected_time_formatted,
        '@now' => $date->format("Y-m-d H:i:s"),
      )));

      $added_days++;
    } // while days
  }

  /**
   * Test a frequency of 2 months.
   */
  function testEditionTimeTwoMonths() {
    // The start date of the edition.
    $this->edition_day = '01';
    $start_date = new \DateTime("2012-01-{$this->edition_day} 12:00:00");

    // Fake newsletter parent data: sets the interval, start date, and frequency.
    $newsletter_parent_data = (object) array(
      'nid' => 1,
      'last_run' => 0,
      'activated' => '1',
      'send_interval' => 'month',
      'interval_frequency' => '2',
      'start_date' => $start_date->getTimestamp(),
      'stop_type' => '0',
      'stop_date' => '0',
      'stop_edition' => '0',
      'php_eval' => '',
      'title' => '[node:title] for [current-date:long]',
    );

    // Number of days to run for. Go just over one year.
    $days = 370;
    // Index of the days we've done so far.
    $added_days = 0;
    // Iterate over days.
    while ($added_days <= $days) {
      // Create today's date at noon and get the timestamp.
      $date = clone($start_date);
      $date->add(new \DateInterval("P{$added_days}D"));
      $timestamp_noon = $date->getTimestamp();

      $edition_time = simplenews_scheduler_calculate_edition_time($newsletter_parent_data, $timestamp_noon);
      //debug($edition_time);

      // Expected edition time is always the {$this->edition_day}th of the month
      // at noon.
      // Note here we use 'n' for the month to avoid having to pad.
      $edition_time_formatted = date("Y-n-d H:i:s", $edition_time);
      $this_year = $date->format('Y');
      $this_month = $date->format('n');
      // We start in January and run 2-monthly.
      // We want the number of elapsed months, module 2 (the frequency), to know
      // the remainder to subtract.
      $elapsed_mod = ($this_month - 1) % 2;
      $expected_month = $this_month - $elapsed_mod;
      $expected_time_formatted = "{$this_year}-{$expected_month}-{$this->edition_day} 12:00:00";

      $this->assertEqual($edition_time_formatted, $expected_time_formatted, t("Edition time of @edition-time matches expected time of @edition-time-expected at time @now.", array(
        '@edition-time' => $edition_time_formatted,
        '@edition-time-expected' => $expected_time_formatted,
        '@now' => $date->format("Y-m-d H:i:s"),
      )));

      $added_days++;
    } // while days
  }

}
