<?php

namespace Drupal\govuk_webform_elements\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform date composite.
 *
 * @group Webform
 */
class WebformDateCompositeTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['govuk_webform_elements'];

  /**
   * Tests webform date element.
   */
  public function testWebformDateComposite() {
    $webform = Webform::load('govuk_webform_elements');

    // Check form element rendering.
    $this->drupalGet('webform/govuk_webform_elements');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<label for="edit-govuk-webform-elements-day">Day</label>');
    $this->assertFieldById('edit-govuk-webform-elements-day');
    $this->assertRaw('<label for="edit-govuk-webform-elements-month">Month</label>');
    $this->assertFieldById('edit-govuk-webform-elements-month');
    $this->assertRaw('<label for="edit-govuk-webform-elements-year">Year</label>');
    $this->assertFieldById('edit-govuk-webform-elements-year');

    // Check webform element submission.
    $edit = [
      'govuk_webform_elements[day]' => '1',
      'govuk_webform_elements[month]' => '12',
      'govuk_webform_elements[year]' => '2018',
      'govuk_webform_elements_multiple[items][0][day]' => '2',
      'govuk_webform_elements_multiple[items][0][month]' => '10',
      'govuk_webform_elements_multiple[items][0][year]' => '2010',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getElementData('govuk_webform_elements'), [
      'day' => '1',
      'month' => '12',
      'year' => '2018',
    ]);
    $this->assertEqual($webform_submission->getElementData('govuk_webform_elements_multiple'), [
      [
        'day' => '2',
        'month' => '10',
        'year' => '2010',
      ],
    ]);
  }

}
