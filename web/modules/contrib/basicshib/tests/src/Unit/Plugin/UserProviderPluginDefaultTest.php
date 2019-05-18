<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:31 AM
 */

namespace Drupal\Tests\basicshib\Unit\Plugin;


use Drupal\basicshib\Plugin\basicshib\user_provider\UserProviderPluginDefault;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

class UserProviderPluginDefaultTest extends UnitTestCase {
  use MockTrait;

  public function testLoadUser() {
    $user = $this->getMockUser(['name' => 'jdoe']);
    $provider = new UserProviderPluginDefault($this->getMockUserStorage([$user]));
    $user = $provider->loadUserByName('jdoe');
    $this->assertTrue(is_a($user, UserInterface::class));
    $this->assertEquals($user->getAccountName(), 'jdoe');

    $user = $provider->loadUserByName('-');
    $this->assertNull($user);
  }

  public function testCreateUser() {
    $provider = new UserProviderPluginDefault($this->getMockUserStorage());
    $user = $provider->createUser('jdoe', 'jdoe@example.com');
    $this->assertNotNull($user);
    $this->assertEquals($user->getAccountName(), 'jdoe');
    $this->assertEquals($user->getEmail(), 'jdoe@example.com');
  }
}
