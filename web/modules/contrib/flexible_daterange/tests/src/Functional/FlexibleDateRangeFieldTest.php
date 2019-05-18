<?php

namespace Drupal\Tests\flexible_daterange\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Tests\datetime\Functional\DateTestBase;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Tests Daterange field functionality.
 *
 * @group datetime
 */
class FlexibleDateRangeFieldTest extends DateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['datetime_range', 'flexible_daterange'];

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   */
  protected $defaultSettings = ['timezone_override' => '', 'separator' => '-'];

  /**
   * {@inheritdoc}
   */
  protected function getTestFieldType() {
    return 'flexible_daterange';
  }

  public function testFlexibleDateRangeFieldShowTime() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    // Ensure the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Start time element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][time]", '', 'End time element found.');
    $this->assertFieldByXPath('//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label, 'Fieldset and label found');
    $this->assertFieldByXPath('//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]', NULL, 'ARIA described-by found');
    $this->assertFieldByXPath('//div[@id="edit-' . $field_name . '-0--description"]', NULL, 'ARIA description found');

    // Build up dates in the UTC timezone.
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $end_value = '2013-06-06 00:00:00';
    $end_date = new DrupalDateTime($end_value, 'UTC');

    // Update the timezone to the system default.
    $start_date->setTimezone(timezone_open(drupal_get_user_timezone()));
    $end_date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $end_date->format($time_format),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));
    $this->assertRaw($end_date->format($date_format));
    $this->assertRaw($end_date->format($time_format));

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
        'format_type' => 'long',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $start_expected_markup = '<time datetime="' . $start_expected_iso . '" class="datetime">' . $start_expected . '</time>';
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected_markup = '<time datetime="' . $end_expected_iso . '" class="datetime">' . $end_expected . '</time>';
    $output = $this->renderTestEntity($id);
    $this->assertContains($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertContains($end_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertContains(' THESEPARATOR ', $output, 'Found proper separator');
  }

  public function testFlexibleDateRangeFieldHideTime() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    // Ensure the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Start time element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][time]", '', 'End time element found.');
    $this->assertFieldByXPath('//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label, 'Fieldset and label found');
    $this->assertFieldByXPath('//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]', NULL, 'ARIA described-by found');
    $this->assertFieldByXPath('//div[@id="edit-' . $field_name . '-0--description"]', NULL, 'ARIA description found');

    // Build up dates in the UTC timezone.
    $value = '2012-12-31 01:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $end_value = '2013-06-06 02:00:00';
    $end_date = new DrupalDateTime($end_value, 'UTC');

    // Update the timezone to the system default.
    $start_date->setTimezone(timezone_open(drupal_get_user_timezone()));
    $end_date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $end_date->format($time_format),
      "{$field_name}[0][hide_time]" => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));
    $this->assertRaw($end_date->format($date_format));
    $this->assertRaw($end_date->format($time_format));

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
        'format_type' => 'long',
        'format_type_hide_time' => 'html_date',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_date = 'class="datetime">' . explode(' ', $value)[0];
    $start_datetime = 'class="datetime">' . $value;

    $end_date = 'class="datetime">' . explode(' ', $end_value)[0];
    $end_datetime = 'class="datetime">' . $end_value;

    $output = $this->renderTestEntity($id);

    $this->assertContains($start_date, $output);
    $this->assertNotContains($start_datetime, $output);
    $this->assertContains($end_date, $output);
    $this->assertNotContains($end_datetime, $output);


    $this->assertContains(' THESEPARATOR ', $output, 'Found proper separator');
  }

}
