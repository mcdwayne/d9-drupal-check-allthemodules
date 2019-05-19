<?php

namespace Drupal\Tests\xero\Unit\Plugin\Validation\Constraint;

use Drupal\Tests\UnitTestCase;
use Drupal\xero\Plugin\Validation\Constraint\XeroGuidConstraint;
use Drupal\xero\Plugin\Validation\Constraint\XeroGuidConstraintValidator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Test the constraint system for Xero Guid strings.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\Validation\Constraint\XeroGuidConstraintValidator
 * @group Xero
 */
class XeroGuidConstraintTest extends UnitTestCase {

  protected $validator;
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Symfony2 validator and context setup from AbstractConstraintValidatorTest.
    $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    $contextualValidator = $this->getMock('Symfony\Component\Validator\Validator\ContextualValidatorInterface');

    $this->validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');

    $this->context = new ExecutionContext($this->validator, 'root', $translator);
    $this->context->setGroup('Xero');
    $this->context->setNode('InvalidValue', null, null, 'property.path');
    $this->context->setConstraint(new XeroGuidConstraint());

    $this->validator->expects($this->any())
      ->method('inContext')
      ->with($this->context)
      ->will($this->returnValue($contextualValidator));

    $this->validator = new XeroGuidConstraintValidator();
    $this->validator->initialize($this->context);
  }

  /**
   * Assert valid GUID values.
   */
  public function testValid() {
    $value = $this->createGuid();
    $this->validator->validate($value, new XeroGuidConstraint());
    $this->assertNoViolation();

    $value = $this->createGuid(FALSE);
    $this->validator->validate($value, new XeroGuidConstraint());
    $this->assertNoViolation();
  }

  /**
   * Assert null value.
   */
  public function testAllowNull() {
    $value = null;
    $this->validator->validate($value, new XeroGuidConstraint());
    $this->assertNoViolation();
  }

  /**
   * Assert bad value.
   */
  public function testNotValid() {
    $value = $this->getRandomgenerator()->string(100);
    $this->validator->validate($value, new XeroGuidConstraint());

    $this->assertCount(1, $this->context->getViolations());
    $violation = $this->context->getViolations()->get(0);
    $this->assertEquals('This value should be a globally-unique identifier.', $violation->getMessageTemplate());
  }

  /**
   * Create a Guid with or without curly braces.
   *
   * @param $braces
   *   (Optional) Return Guid wrapped in curly braces.
   * @return string
   *   Guid string.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtoupper(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    // A Guid string representation should be output as lower case per UUIDs
    // and GUIDs Network Working Group INTERNET-DRAFT 3.3.
    $guid = strtolower($guid);

    if ($braces) {
      return '{' . $guid . '}';
    }

    return $guid;
  }

  /**
   * Ripped from Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest
   * because I cannot extend it because Drupal.
   */
  protected function assertNoViolation() {
    $this->assertCount(0, $this->context->getViolations());
  }
}
