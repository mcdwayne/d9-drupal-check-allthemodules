<?php

namespace Drupal\Tests\fac\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\fac\HashService;

/**
 * Class HashServiceTest.
 *
 * @group fac
 */
class HashServiceTest extends UnitTestCase {
  protected $container;
  public $hashService;

  /**
   * Sets up the Test class.
   */
  public function setup() {
    parent::setUp();
    $this->container = new ContainerBuilder();
    $current_user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $current_user->expects($this->any())->method('getRoles')->will($this->returnValue(['anonymous']));
    $this->container->set('current_user', $current_user);
    $state = $this->getMock('Drupal\Core\State\StateInterface');
    $this->container->set('state', $state);
    \Drupal::setContainer($this->container);

    $this->hashService = new HashService($state, $current_user);
  }

  /**
   * Tests the getKey() method of the HashService.
   */
  public function testGetKey() {
    $this->assertNotEmpty($this->hashService->getKey());
  }

  /**
   * Tests the getHash() method of the HashService.
   */
  public function testGetHash() {
    $this->assertNotEmpty($this->hashService->getHash());
  }

  /**
   * Tests the isValidHash() method of the HashService.
   */
  public function testIsValidHash() {
    $hash = 'invalidhash';
    $this->assertFalse($this->hashService->isValidHash($hash));

    // TODO: Figure out how to get the state mock to return a key that ensures
    // the hashes are the same.
    $state = $this->getMock('Drupal\Core\State\StateInterface');
    $state->expects($this->any())->method('get')->will($this->returnValueMap([
      ['fac_key', 'testkey'],
      ['fac_key_timestamp', 1],
    ]));
    $this->container->set('state', $state);
    $hash = $this->hashService->getHash();
    $this->assertTrue($this->hashService->isValidHash($hash));
  }

}
