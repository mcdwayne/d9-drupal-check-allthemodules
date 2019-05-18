<?php

namespace Drupal\Tests\fapi_validation\Unit\Validators;

use Drupal\fapi_validation\Plugin\FapiValidationValidator\DecimalValidator;
use Drupal\fapi_validation\Validator;

/**
 * Tests generation of ice cream.
 *
 * @group fapi_validation
 * @group fapi_validation_validators
 */
class DecimalValidatorTest extends BaseValidator {

  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->plugin = new DecimalValidator();
  }

  /**
   * Testing decimal and negative decimal without params.
   */
  public function testNegativeDecimalNoParams() {
    $validator = new Validator('decimal', '123.23');
    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));

    $validator = new Validator('decimal', '-123.23');
    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));
  }

  /**
   * Testing integer.
   */
  public function testIntegerNoParams() {
    $validator = new Validator('decimal', '1525');
    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));

    $validator = new Validator('decimal', '-1525');
    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));
  }

  /**
   * Testing negative decimal value.
   */
  public function testNegativeDecimal() {
    $validator = new Validator('decimal', '-123.23');

    $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));
  }

    /**
     * Testing decimal and negative decimal without params.
     */
    public function testNegativeDecimalWithParams() {
      $validator = new Validator('decimal[3,2]', '123.23');
      $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));

      $validator = new Validator('decimal[3,2]  ', '-123.23');
      $this->assertTrue($this->plugin->validate($validator, [], $this->decoratedFormState));

      $validator = new Validator('decimal[5,2]', '123.23');
      $this->assertFalse($this->plugin->validate($validator, [], $this->decoratedFormState));

      $validator = new Validator('decimal[3,3]', '123.23');
      $this->assertFalse($this->plugin->validate($validator, [], $this->decoratedFormState));
    }

}
