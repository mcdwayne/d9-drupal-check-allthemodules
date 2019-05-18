<?php

namespace Drupal\Tests\fapi_validation\Unit\Validators;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests generation of ice cream.
 *
 * @group fapi_validation
 * @group fapi_validation_validators
 */
abstract class BaseValidator extends UnitTestCase {

  /**
   * The decorated form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $decoratedFormState;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->decoratedFormState = $this->prophesize(FormStateInterface::class)->reveal();
  }

}
