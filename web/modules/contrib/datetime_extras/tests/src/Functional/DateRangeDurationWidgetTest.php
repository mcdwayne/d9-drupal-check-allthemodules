<?php

namespace Drupal\Tests\datetime_extras\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Tests\datetime\Functional\DateTestBase;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Test the DateRangeDurationWidget for datetime_range fields.
 *
 * @group datetime_extras
 */
class DateRangeDurationWidgetTest extends DateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'datetime_range',
    'datetime_extras',
    'duration_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getTestFieldType() {
    return 'daterange';
  }

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   */
  protected $defaultSettings = ['timezone_override' => '', 'separator' => '-'];

  /**
   * Tests Date Range List Widget functionality.
   */
  public function testDateRangeDurationWidget() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure field is set to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Change the widget to a daterange_duration with some default settings.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_duration',
        'settings' => [
          'duration_granularity' => 'd:h:i',
          'time_increment' => '300',
          'default_duration' => [
            'h' => '2',
            'i' => '15',
          ],
        ],
      ])
      ->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Start time element found.');

    $end_type_id = "edit-{$field_name}-0-end-type";
    $this->assertTrue($this->xpath('//div[@id=:id]//input[@value=:value]', [':id' => $end_type_id, ':value' => 'duration']), 'A radio button has a "Duration" choice.');
    $this->assertTrue($this->xpath('//div[@id=:id]//input[@value=:value]', [':id' => $end_type_id, ':value' => 'end_date']), 'A radio button has an "End date" choice.');

    // No JS, these should still be visible.
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][time]", '', 'End time element found.');

    // Check the duration field elements.
    // Make sure granularity setting works so that  y, m and s are gone:
    $this->assertNoFieldByXpath("//input[@id = 'edit-{$field_name}-0-duration-y']", NULL, 'Duration years is not shown.');
    $this->assertNoFieldByXpath("//input[@id = 'edit-{$field_name}-0-duration-m']", NULL, 'Duration months is not shown.');
    $this->assertNoFieldByXpath("//input[@id = 'edit-{$field_name}-0-duration-s']", NULL, 'Duration seconds is not shown.');

    // Make sure the default duration setting works on the remaining elements:
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '0', 'Default duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '2', 'Default duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '15', 'Default duration minutes is set correctly.');

    // Build up dates in the UTC timezone.
    $value = '1917-11-07 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    // First, submit a start date, using the default duration (and no end
    // date) and ensure the end_value is set correctly:
    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_type]" => 'duration',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));
    // The end date is hidden by default, so we can't just assertRaw() for the
    // date or time. Instead, assert the field values in the widget.
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '0', 'Duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '2', 'Duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '15', 'Duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1917-11-07', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '02:15:00', 'End time is set correctly.');

    // Now, submit a start date, change the duration, still no end date:
    $start_date = new DrupalDateTime('1917-11-07 03:05:00', 'UTC');
    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_type]" => 'duration',
      // 10 days that shook the world.
      "{$field_name}[0][duration][d]" => 10,
      // And a little more, to keep things interesting.
      "{$field_name}[0][duration][h]" => 1,
      "{$field_name}[0][duration][i]" => 30,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Make sure the new start time is happening.
    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));

    // Check that the duration and end_value is correct:
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '10', 'Duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '1', 'Duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '30', 'Duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1917-11-17', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '04:35:00', 'End time is set correctly.');

    // Now, set a new end time directly, leaving the duration values in the
    // form, and see that when the page reloads, we have the right end time and
    // the correct new duration values pre-filled.
    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_type]" => 'end_date',
      "{$field_name}[0][end_value][date]" => '1917-11-11',
      "{$field_name}[0][end_value][time]" => '08:45:00',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));

    // start_date is '1917-11-07 03:05:00'.
    // end_value is  '1917-11-11 08:45:00'.
    // Duration should be 4 days, 5 hours, 40 minutes.
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '4', 'Duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '5', 'Duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '40', 'Duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1917-11-11', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '08:45:00', 'End time is set correctly.');

    // Now, change the widget settings to use the full duration granularity.
    // Change the widget to a daterange_duration with some default settings.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_duration',
        'settings' => [
          'duration_granularity' => 'y:m:d:h:i:s',
          'time_increment' => '1',
          'default_duration' => [
            'd' => '2',
            'h' => '4',
            'i' => '20',
            's' => '5',
          ],
        ],
      ])
      ->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Start over with a new entity.
    $this->drupalGet('entity_test/add');

    // Make sure granularity works and we see all the duration elements:
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-y']"), '0', 'Default duration years is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-m']"), '0', 'Default duration months is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '2', 'Default duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '4', 'Default duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '20', 'Default duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-s']"), '5', 'Default duration seconds is set correctly.');

    // Ensure the default duration works with all these elements.
    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_type]" => 'duration',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));

    // None of this should have changed.
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-y']"), '0', 'Duration years is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-m']"), '0', 'Duration months is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '2', 'Duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '4', 'Duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '20', 'Duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-s']"), '5', 'Duration seconds is set correctly.');

    // start_date is '1917-11-07 03:05:00'.
    // end_value should be '1917-11-09 07:25:05'.
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1917-11-09', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '07:25:05', 'End time is set correctly.');

    // Try to use all the duration elements with unique values.
    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_type]" => 'duration',
      "{$field_name}[0][duration][y]" => 2,
      "{$field_name}[0][duration][m]" => 1,
      "{$field_name}[0][duration][d]" => 4,
      "{$field_name}[0][duration][h]" => 5,
      "{$field_name}[0][duration][i]" => 10,
      "{$field_name}[0][duration][s]" => 45,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));

    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-y']"), '2', 'Duration years is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-m']"), '1', 'Default duration months is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '4', 'Default duration days is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '5', 'Default duration hours is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '10', 'Default duration minutes is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-s']"), '45', 'Default duration seconds is set correctly.');

    // start_date is '1917-11-07 03:05:00'.
    // end_value should be '1919-12-11 08:15:45'.
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1919-12-11', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '08:15:45', 'End time is set correctly.');

    // Test the widget for validation notifications.
    // Change the widget settings to use the full duration granularity, but
    // no default duration.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_duration',
        'settings' => [
          'duration_granularity' => 'y:m:d:h:i:s',
          'time_increment' => '1',
          'default_duration' => [],
        ],
      ])
      ->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Define a start time, pick duration, set it to empty, end time should be
    // the same as start time.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value][date]" => '1917-11-07',
      "{$field_name}[0][value][time]" => '03:00:00',
      "{$field_name}[0][end_type]" => 'duration',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-date']"), '1917-11-07', 'End date is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-end-value-time']"), '03:00:00', 'End time is set correctly.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-y']"), '0', 'Duration years is empty.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-m']"), '0', 'Default duration months is empty.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-d']"), '0', 'Default duration days is empty.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-h']"), '0', 'Default duration hours is empty.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-i']"), '0', 'Default duration minutes is empty.');
    $this->assertFieldsByValue($this->xpath("//input[@id = 'edit-{$field_name}-0-duration-s']"), '0', 'Default duration seconds is empty.');

    // Define a start time, use end_date radio, but leave it empty.
    $this->drupalGet('entity_test/add');
    $edit = [
      "{$field_name}[0][value][date]" => '1917-11-07',
      "{$field_name}[0][value][time]" => '03:00:00',
      "{$field_name}[0][end_type]" => 'end_date',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText(t('You must define either a duration or an end date.'));

    // Now, set the field to not be required and try again.
    $this->field->setRequired(FALSE)->save();

    // We should hit core's validation error about a partial daterange value.
    $this->drupalGet('entity_test/add');
    // Intentionally re-using the same $edit array from above.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText(t('This value should not be null.'));

    // Completely empty values, field isn't required, should be no problem.
    $this->drupalGet('entity_test/add');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertResponse(200);
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
  }

}
