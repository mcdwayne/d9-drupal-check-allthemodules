<?php

namespace Drupal\webform_iban_field\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform_iban_field.
 *
 * @group Webform
 */
class WebformIbanFieldTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_iban_field'];

  /**
   * Tests IBAN field.
   */
  public function testWebformIbanField() {
    $webform = Webform::load('webform_iban_field');

    // Check form element rendering.
    $this->drupalGet('webform/webform_iban_field');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-iban-field form-type-webform-iban-field js-form-item-webform-iban-field form-item-webform-iban-field">');
    $this->assertRaw('<label for="edit-webform-iban-field">Webform IBAN field</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-iban-field" type="text" id="edit-webform-iban-field" name="webform_iban_field" value="" size="60" class="form-text webform-iban-field" />');

    // Check webform element submission.
    $edit = [
      'webform_iban_field' => '{Test}',
      'webform_iban_field_multiple[items][0][_item_]' => '{Test 01}',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getElementData('webform_iban_field'), '{Test}');
    $this->assertEqual($webform_submission->getElementData('webform_iban_field_multiple'), ['{Test 01}']);
  }

}
