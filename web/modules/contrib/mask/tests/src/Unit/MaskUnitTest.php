<?php

namespace Drupal\Tests\mask\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for Mask Field's unit tests.
 *
 * @group mask
 */
abstract class MaskUnitTest extends UnitTestCase {

  /**
   * Module settings object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Translation settings.
   *
   * @var array
   */
  protected $translation = [
    '0' => [
      'pattern' => '\d',
    ],
    '9' => [
      'pattern' => '\d',
      'optional' => TRUE,
    ],
    '#' => [
      'pattern' => '\d',
      'recursive' => TRUE,
    ],
    'A' => [
      'pattern' => '[a-zA-Z0-9]',
    ],
    'S' => [
      'pattern' => '[a-zA-Z]',
    ],
  ];

  /**
   * A configuration factory mock with Mask Field's settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mocks a configuration for Mask Field.
    $this->config = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
                         ->disableOriginalConstructor()
                         ->setMethods(['get'])
                         ->getMock();
    $this->config->expects($this->any())
                 ->method('get')
                 ->with($this->equalTo('translation'))
                 ->will($this->returnValue($this->translation));

    // Mocks a configuration factory.
    $this->configFactory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->configFactory->expects($this->any())
                        ->method('get')
                        ->with($this->equalTo('mask.settings'))
                        ->will($this->returnValue($this->config));
  }

}
