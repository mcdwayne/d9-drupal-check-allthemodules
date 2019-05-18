<?php

namespace Drupal\Tests\norwegian_id\Unit\Plugin\Validation;

use Drupal\norwegian_id\Plugin\Validation\Constraint\NorwegianIdConstraint;
use Drupal\norwegian_id\Plugin\Validation\Constraint\NorwegianIdConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests NorwegianIdConstraintValidator.
 *
 * @coversDefaultClass \Drupal\norwegian_id\Plugin\Validation\Constraint\NorwegianIdConstraintValidator
 * @group norwegian_id
 */
class NorwegianIdConstraintValidatorTest extends UnitTestCase {

  /**
   * Holds a NorwegianIdConstraint object.
   *
   * @var \Drupal\norwegian_id\Plugin\Validation\Constraint\NorwegianIdConstraint
   */
  protected $constraint;

  /**
   * Holds a NorwegianIdConstraintValidator object.
   *
   * @var \Drupal\norwegian_id\Plugin\Validation\Constraint\NorwegianIdConstraintValidator
   */
  protected $validator;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->constraint = new NorwegianIdConstraint();
    $this->validator  = new NorwegianIdConstraintValidator();
  }

  /**
   * @covers ::validate
   *
   * @dataProvider norwegianIdProvider
   */
  public function testValidate($norwegian_id, $expected_violation) {
    // If a violation is expected, then the context's addViolation method
    // will be called, otherwise it should not be called.
    $context = $this->prophesize(ExecutionContextInterface::class);

    if ($expected_violation) {
      $violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
      $context->addViolation($expected_violation)
        ->willReturn($violation_builder->reveal())
        ->shouldBeCalled();
    }
    else {
      $context->addViolation(Argument::any())->shouldNotBeCalled();
    }

    $this->validator->initialize($context->reveal());
    $this->validator->validate($norwegian_id, $this->constraint);
  }

  /**
   * Data provider for ::testValidate().
   */
  public function norwegianIdProvider() {
    $constraint = new NorwegianIdConstraint();

    return [
      '11 Oct 1985'             => ['11108526965', FALSE],
      '31 Dec 2008'             => ['31120894539', FALSE],
      '21 Aug 1975'             => ['21087593549', FALSE],
      'Invalid format'          => ['300287269', $constraint->invalidFormatMessage],
      'Wrong date: 30 Feb'      => ['30028726900', $constraint->invalidBirthMessage],
      'Wrong individual number' => ['20011122066', $constraint->invalidBirthMessage],
      'Wrong 1st check digit'   => ['11108526905', $constraint->invalidControlDigitsMessage],
      'Wrong 2nd check digit'   => ['11108526960', $constraint->invalidControlDigitsMessage],
    ];
  }

}
