<?php

namespace Drupal\Tests\user_agent_class\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\user_agent_class\CheckAgentServices;

/**
 * Simple test to check functional.
 *
 * @group user_agent_class
 */
class CheckUserAgentTest extends UnitTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_agent_class'];

  /**
   * The check Agent Services.
   *
   * @var \Drupal\user_agent_class\CheckAgentServicesInterface
   */
  protected $checkAgentServices;

  /**
   * Object for testing.
   *
   * @var \Drupal\user_agent_class\Entity\UserAgentEntity
   */
  protected $userAgentEntityFirst;

  /**
   * Object for testing.
   *
   * @var \Drupal\user_agent_class\Entity\UserAgentEntity
   */
  protected $userAgentEntitySecond;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->checkAgentServices = $this->getMockBuilder(CheckAgentServices::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->userAgentEntityFirst = $this->getMockBuilder('Drupal\user_agent_class\Entity\UserAgentEntity')
      ->disableOriginalConstructor()
      ->getMock();

    $this->userAgentEntitySecond = $this->getMockBuilder('Drupal\user_agent_class\Entity\UserAgentEntity')
      ->disableOriginalConstructor()
      ->getMock();

    $this->userAgentEntityFirst->method('label')
      ->willReturn('Chrome');
    $this->userAgentEntityFirst->method('getClassName')
      ->willReturn('chrome');

    $this->userAgentEntitySecond->method('label')
      ->willReturn('Firefox');
    $this->userAgentEntitySecond->method('getClassName')
      ->willReturn('firefox');
  }

  /**
   * Test get class name from list of entities.
   */
  public function testGetClassNameFromList() {
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36';
    $expected = 'chrome';

    $this->assertEquals($expected, $this->checkAgentServices->getClassNameFromList([
      $this->userAgentEntityFirst,
      $this->userAgentEntitySecond,
    ], $userAgent));
  }

  /**
   * Test create separate list with classes and triggers.
   */
  public function testCreateSeparateListHelper() {
    $expected = [
      'user_agent_entity' => [
        '0' => [
          'trigger' => 'Chrome',
          'className' => 'chrome',
          'exclude' => NULL,
        ],
        '1' => [
          'trigger' => 'Firefox',
          'className' => 'firefox',
          'exclude' => NULL,
        ],
      ],
    ];

    $this->assertEquals($expected, $this->checkAgentServices->createSeparateListHelper('user_agent_entity', [
      $this->userAgentEntityFirst,
      $this->userAgentEntitySecond,
    ]));
  }

}
