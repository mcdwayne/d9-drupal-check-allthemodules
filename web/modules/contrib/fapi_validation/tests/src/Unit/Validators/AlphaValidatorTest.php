<?php

namespace Drupal\Tests\fapi_validation\Unit\Validators;

use Drupal\fapi_validation\Plugin\FapiValidationValidator\AlphaValidator;
use Drupal\fapi_validation\Validator;

/**
 * Tests generation of ice cream.
 *
 * @group fapi_validation
 * @group fapi_validation_validators
 */
class AlphaValidatorTest extends BaseValidator {

  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->plugin = new AlphaValidator();
  }

  /**
   * Testin valid string.
   */
  public function testValidString() {
    $validator = new Validator('alpha', 'SimpleAlpha');

    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));
  }

  /**
   * Testing invalid string.
   */
  public function testInvalidString() {
    $validator = new Validator('alpha', 'SimpleAlpha With !!@$@!Invalid');

    $this->assertFalse($this->plugin->validate($validator, [], $this->decoratedFormState));
  }

}
