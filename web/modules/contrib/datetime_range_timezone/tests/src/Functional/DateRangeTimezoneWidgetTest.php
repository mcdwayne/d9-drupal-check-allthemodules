<?php

namespace Drupal\Tests\datetime_range_timezone\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;

/**
 * Test the datetime widget.
 *
 * @group datetime_range_timezone
 */
class DateRangeTimezoneWidgetTest extends DateRangeTimezoneTestBase {

  /**
   * Ensure that default values are translated into the selected timezone.
   */
  public function testWidgetDefaultValuesAreTranslated() {
    $assert = $this->assertSession();

    // Create an entity with a date that we'll enter in Amercia/New York time.
    $start_date = new DrupalDateTime('2017-03-25 10:30:00', 'America/New_York');
    $end_date = new DrupalDateTime('2017-03-28 10:30:00', 'America/New_York');

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $this->drupalPostForm('entity_test/add', [
      'date[0][value][date]' => $start_date->format($date_format),
      'date[0][value][time]' => $start_date->format($time_format),
      'date[0][end_value][date]' => $end_date->format($date_format),
      'date[0][end_value][time]' => $end_date->format($time_format),
      'date[0][timezone]' => 'America/New_York',
    ], 'Save');

    $assert->fieldValueEquals('date[0][value][date]', $start_date->format($date_format));
    $assert->fieldValueEquals('date[0][value][time]', $start_date->format($time_format));
    $assert->fieldValueEquals('date[0][end_value][date]', $end_date->format($date_format));
    $assert->fieldValueEquals('date[0][end_value][time]', $end_date->format($time_format));
  }

}
