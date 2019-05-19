<?php

/**
 * @file
 * Simplenews scheduler DST changes test functions.
 *
 * @ingroup simplenews_scheduler
 */

namespace Drupal\simplenews_scheduler\Tests;

/**
 * Test edition time after DST changes for a monthly newsletter.
 *
 * @group simplenews_scheduler
 */
class SimplenewsSchedulerDaylightSavingSwitchTest extends SimplenewsSchedulerWebTestBase {

  /**
   * Test edition time after DST changes for a monthly newsletter.
   *
   * @todo: generalize this for other intervals.
   */
  function testDSTMonthly() {
    $timezone_name = date_default_timezone_get();
    //debug($timezone_name);

    // Create a last run time before DST begins, and a now time after.
    // Use date_create() rather than strtotime so that we create a date at the
    // given time *in the current timezone* rather than UTC.
    $last_run_date = new \DateTime("01-Mar-12 12:00:00");
    $now_date = date_create("05-Apr-12 12:00:00");

    //debug('last run date TZ: ' . $last_run_date->getTimezone()->getName());
    //debug('now date TZ: ' . $now_date->getTimezone()->getName());

    // Fake up newsletter data.
    $newsletter_parent_data = (object) array(
      'last_run' => $last_run_date->getTimestamp(),
      'send_interval' => 'month',
      'interval_frequency' => 1,
    );

    // Get the edition time.
    $edition_time = simplenews_scheduler_calculate_edition_time($newsletter_parent_data, $now_date->getTimestamp());

    $edition_date = date_create('@' . $edition_time);
    //debug($edition_date->format(DATE_ATOM));

    // Format the edition time.
    $edition_time_formatted = format_date($edition_time, 'custom', DATE_ATOM);
    $edition_hour_formatted = format_date($edition_time, 'custom', 'H:i');

    $this->assertEqual($edition_hour_formatted, '12:00', t('Edition time is at 12:00 in the local timezone; full edition time is %time.', array(
      '%time' => $edition_time_formatted,
    )));
  }

}