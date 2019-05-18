<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 10:08 AM
 */

namespace Drupal\Tests\basicshib\Unit\Plugin;


use Drupal\basicshib\Plugin\AuthFilterPluginInterface;
use Drupal\basicshib\Plugin\basicshib\auth_filter\AuthFilterPluginDefault;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Drupal\Tests\UnitTestCase;

class AuthFilterPluginDefaultTest extends UnitTestCase {
  use MockTrait;

  public function testIsUserCreationAllowedWhenTrue() {
    $config_factory = $this->getMockConfigFactory([
      'basicshib.auth_filter' => [
        'create' => ['allow' => true],
      ]
    ]);

    $plugin = new AuthFilterPluginDefault(
      $config_factory->get('basicshib.auth_filter')
    );

    $this->assertTrue($plugin->isUserCreationAllowed());
  }

  public function testIsUserCreationAllowedWhenFalse() {
    $config_factory = $this->getMockConfigFactory([
      'basicshib.auth_filter' => [
        'create' => ['allow' => false],
      ]
    ]);

    $plugin = new AuthFilterPluginDefault(
      $config_factory->get('basicshib.auth_filter')
    );

    $this->assertFalse($plugin->isUserCreationAllowed());
  }

  public function testGetUserCreationErrorMessage() {
    $config_factory = $this->getMockConfigFactory();

    $plugin = new AuthFilterPluginDefault(
      $config_factory->get('basicshib.auth_filter')
    );
    $this->assertEquals('user_creation_not_allowed', $plugin->getError(AuthFilterPluginInterface::ERROR_CREATION_NOT_ALLOWED));
  }

}
