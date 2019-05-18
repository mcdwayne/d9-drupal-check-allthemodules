<?php

namespace Drupal\Tests\konamicode\Unit;

use Drupal\konamicode\Form\KonamicodeActionAlertConfiguration;
use Drupal\Tests\UnitTestCase;

/**
 * Class KonamicodeKeyCodeSequenceTest.
 *
 * @group konamicode
 */
class KonamicodeKeyCodeSequenceTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The action class used to trigger the functions.
   *
   * @var \Drupal\konamicode\Form\KonamicodeActionAlertConfiguration
   */
  protected $actionClass;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this
      ->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Just mock that we are one of the action classes in order to be able to
    // call the validation function of the base class.
    $this->actionClass = new KonamicodeActionAlertConfiguration($this->configFactory);
  }

  /**
   * Function to test all allowed values.
   */
  public function testKeyCodeSequenceValidationAllowedValues() {
    // The one and only real Konami Code.
    $this->assertTrue($this->actionClass->validateKeyCodeSequence('38,38,40,40,37,39,37,39,66,65'));
    // Single digit.
    $this->assertTrue($this->actionClass->validateKeyCodeSequence('1'));
    // Numbers with one digit.
    $this->assertTrue($this->actionClass->validateKeyCodeSequence('1,2,3,4,5,6,7,8'));
    // Numbers with three digits.
    $this->assertTrue($this->actionClass->validateKeyCodeSequence('123,456,789'));
  }

  /**
   * Function to test all disallowed values.
   */
  public function testKeyCodeSequenceValidationDisallowedValues() {
    // Leading ',' is not accepted.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence(',38,38,40,40,37,39,37,39,66,65'));
    // Trailing ',' is not accepted.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence('38,38,40,40,37,39,37,39,66,65,'));
    // Text is not allowed.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence('38,38,40,40,37,test,37,39,66,65'));
    // No value isn't allowed.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence(''));
    // Longer as 3 characters is not allowed.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence('38,38,40,40,37,39,37,39,66,65789'));
    // Spaces are not allowed.
    $this->assertFalse($this->actionClass->validateKeyCodeSequence(' 38,38,40,40,37,39,37,39,66,65789'));
    $this->assertFalse($this->actionClass->validateKeyCodeSequence('38,38,40,40,37,39,37,39,66,65789 '));
    $this->assertFalse($this->actionClass->validateKeyCodeSequence('38,38,40,40,37, 39 ,37,39,66,65789'));
  }

}
