<?php

/**
 * @file
 * Contains \Drupal\Tests\simple_ldap\Unit\SimpleLdapTestBase
 */

namespace Drupal\Tests\simple_ldap\Unit;

use Drupal\simple_ldap\SimpleLdapException;
use Drupal\Tests\UnitTestCase;
use Drupal\simple_ldap\SimpleLdapServer;

abstract class SimpleLdapTestBase extends UnitTestCase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;

  /**
   * @var string
   * Used in ::setUp. Set a default value in any inherited class.
   */
  protected $config_name;

  /**
   * {@inheritdoc}
   *
   * Sets up common service mocks for SimpleLdap unit tests.
   *
   * @param string $config_name
   */
  public function setUp() {
    parent::setUp();

    $this->config = $this->getMockBuilder('\Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()
      ->getMock();

    $this->config_factory = $this->getMockBuilder('\Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->config_factory->expects($this->once())
      ->method('get')
      ->with($this->config_name)
      ->willReturn($this->config);
  }
}
