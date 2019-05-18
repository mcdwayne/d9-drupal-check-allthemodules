<?php

namespace Drupal\Tests\date_time_day\Functional;

use Drupal\Tests\datetime\Functional\DateTestBase;
use Drupal\date_time_day\Plugin\Field\FieldType\DateTimeDayItem;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests date_time_day field functionality.
 *
 * @group date_time_day
 */
class DateTimeDayFieldTest extends DateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['date_time_day'];

  /**
   * An array of timezone extremes to test.
   *
   * @var string[]
   */
  protected static $timezones = [
    // UTC.
    'UTC',
  ];

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   */
  protected $defaultSettings = [
    'timezone_override' => '',
    'day_separator' => ',',
    'time_separator' => '-',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getTestFieldType() {
    return 'datetimeday';
  }

  /**
   * Test the default field type and widget.
   */
  public function testDateTimeDayTypeDefaultWithWidgetField() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();
    // Loop through defined timezones to test that date-only fields work at the
    // extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);
      // Ensure field is set to a date-only field.
      $this->fieldStorage->setSetting('datetime_type', DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT);
      $this->fieldStorage->save();
      // Set correct form widget type.
      EntityFormDisplay::load('entity_test.entity_test.default')
        ->setComponent($field_name, ['type' => 'datetimeday_default'])
        ->save();
      // Display creation form.
      $this->drupalGet('entity_test/add');
      $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
      $this->assertFieldByName("{$field_name}[0][start_time_value]", '', 'Start time element found.');
      $this->assertFieldByName("{$field_name}[0][end_time_value]", '', 'End time element found.');
      $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]//label[contains(@class, "js-form-required")]', TRUE, 'Required markup found');
      $this->assertFieldByXPath('//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label, 'Fieldset and label found');
      $this->assertFieldByXPath('//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]', NULL, 'ARIA described-by found');
      $this->assertFieldByXPath('//div[@id="edit-' . $field_name . '-0--description"]', NULL, 'ARIA description found');
      // Build up dates in the UTC timezone.
      $date_value = '2012-12-30 00:00:00';
      $date = new DrupalDateTime($date_value, 'UTC');
      $start_time_value = '10:00';
      $end_time_value = '19:00';
      // Submit a valid date and ensure it is accepted.
      $date_format = DateFormat::load('html_date')->getPattern();

      $edit = [
        "{$field_name}[0][value][date]" => $date->format($date_format),
        "{$field_name}[0][start_time_value]" => $start_time_value,
        "{$field_name}[0][end_time_value]" => $end_time_value,
      ];
      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
      $this->assertRaw('2012-12-30');
      $this->assertRaw($start_time_value);
      $this->assertRaw($end_time_value);
      // Verify the date doesn't change when entity is edited through the form.
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-30', $entity->{$field_name}->value);
      $this->assertEqual($start_time_value, $entity->{$field_name}->start_time_value);
      $this->assertEqual($end_time_value, $entity->{$field_name}->end_time_value);
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-30', $entity->{$field_name}->value);
      $this->assertEqual($start_time_value, $entity->{$field_name}->start_time_value);
      $this->assertEqual($end_time_value, $entity->{$field_name}->end_time_value);
    }
  }

  /**
   * Test with seconds field type and widget.
   */
  public function testDateTimeDayTypeSecondsWithWidgetField() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();
    // Loop through defined timezones to test that date-only fields work at the
    // extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone('UTC');
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);
      // Ensure field is set to a date-only field.
      $this->fieldStorage->setSetting('datetime_type', DateTimeDayItem::DATEDAY_TIME_TYPE_SECONDS_FORMAT);
      // Set correct form widget type.
      EntityFormDisplay::load('entity_test.entity_test.default')
        ->setComponent($field_name, ['type' => 'datetimeday_h_i_s_time'])
        ->save();
      $this->fieldStorage->save();
      // Display creation form.
      $this->drupalGet('entity_test/add');
      $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
      $this->assertFieldByName("{$field_name}[0][start_time_value][time]", '', 'Start time element found.');
      $this->assertFieldByName("{$field_name}[0][end_time_value][time]", '', 'End time element found.');
      $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]//label[contains(@class, "js-form-required")]', TRUE, 'Required markup found');
      $this->assertFieldByXPath('//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label, 'Fieldset and label found');
      $this->assertFieldByXPath('//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]', NULL, 'ARIA described-by found');
      $this->assertFieldByXPath('//div[@id="edit-' . $field_name . '-0--description"]', NULL, 'ARIA description found');
      // Build up dates in the UTC timezone.
      $date_value = '2012-12-30 00:00:00';
      $date = new DrupalDateTime($date_value, 'UTC');
      $start_time_value = '22:10:10';
      $end_time_value = '19:19:19';
      // Submit a valid date and ensure it is accepted.
      $date_format = DateFormat::load('html_date')->getPattern();

      $edit = [
        "{$field_name}[0][value][date]" => $date->format($date_format),
        "{$field_name}[0][start_time_value][time]" => $start_time_value,
        "{$field_name}[0][end_time_value][time]" => $end_time_value,
      ];
      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
      $this->assertRaw('2012-12-30');
      $this->assertRaw($start_time_value);
      $this->assertRaw($end_time_value);
      // Verify the date doesn't change when entity is edited through the form.
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-30', $entity->{$field_name}->value);
      $this->assertEqual($start_time_value, $entity->{$field_name}->start_time_value);
      $this->assertEqual($end_time_value, $entity->{$field_name}->end_time_value);
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-30', $entity->{$field_name}->value);
      $this->assertEqual($start_time_value, $entity->{$field_name}->start_time_value);
      $this->assertEqual($end_time_value, $entity->{$field_name}->end_time_value);
    }
  }

  /**
   * Tests that field storage setting form is disabled if field has data.
   */
  public function testDateStorageSettings() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'datetimeday',
      'settings' => [
        'datetime_type' => DateTimeDayItem::DATEDAY_TIME_DEFAULT_TYPE_FORMAT,
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'field_name' => $field_name,
      'bundle' => 'date_content',
    ]);
    $field->save();

    entity_get_form_display('node', 'date_content', 'default')
      ->setComponent($field_name, [
        'type' => 'datetimeday_default',
      ])
      ->save();
    $edit = [
      'title[0][value]' => $this->randomString(),
      'body[0][value]' => $this->randomString(),
      $field_name . '[0][value][date]' => '2016-04-01',
      $field_name . '[0][start_time_value]' => '10:00',
      $field_name . '[0][end_time_value]' => '19:00',
    ];
    $this->drupalPostForm('node/add/date_content', $edit, t('Save'));
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name . '/storage');
    $result = $this->xpath("//*[@id='edit-settings-datetime-type' and contains(@disabled, 'disabled')]");
    $this->assertEqual(count($result), 1, "Changing datetime setting is disabled.");
    $this->assertText('There is data for this field in the database. The field settings can no longer be changed.');
  }

}
