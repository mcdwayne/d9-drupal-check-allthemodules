<?php

namespace Drupal\Tests\konamicode\Unit;

use Drupal\konamicode\Form\KonamicodeActionSnowfallConfiguration;
use Drupal\Tests\UnitTestCase;

/**
 * Class KonamicodeHexColorTest.
 *
 * @group konamicode
 */
class KonamicodeHexColorTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The action class used to trigger the functions.
   *
   * @var \Drupal\konamicode\Form\KonamicodeActionSnowfallConfiguration
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

    // Mock that we are the snowfall class.
    $this->actionClass = new KonamicodeActionSnowfallConfiguration($this->configFactory);
  }

  /**
   * Function to test all allowed values.
   */
  public function testHexColorValidationAllowedValues() {
    // 3 char.
    $this->assertTrue($this->actionClass->validateFlakeColor('#fff'));
    // 6 char.
    $this->assertTrue($this->actionClass->validateFlakeColor('#ffffff'));
    // 3 numbers.
    $this->assertTrue($this->actionClass->validateFlakeColor('#000'));
    // 6 numbers.
    $this->assertTrue($this->actionClass->validateFlakeColor('#000000'));
  }

  /**
   * Function to test all disallowed values.
   */
  public function testHexColorValidationDisallowedValues() {
    // No #.
    $this->assertFalse($this->actionClass->validateFlakeColor('fff'));
    // Empty.
    $this->assertFalse($this->actionClass->validateFlakeColor(''));
    // Longer as 6.
    $this->assertFalse($this->actionClass->validateFlakeColor('#ffffffffff'));
    // .fff.
    $this->assertFalse($this->actionClass->validateFlakeColor('.fff'));
    // .ffffff.
    $this->assertFalse($this->actionClass->validateFlakeColor('.ffffff'));
  }

}
