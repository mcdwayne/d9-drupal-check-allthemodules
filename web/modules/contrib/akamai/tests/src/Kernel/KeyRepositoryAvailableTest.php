<?php

namespace Drupal\Tests\akamai\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests key provider functionality with key module is installed.
 *
 * @group Akamai
 */
class KeyRepositoryAvailableTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['akamai', 'key'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['akamai', 'key']);
    $this->generateKeys();
  }

  /**
   * Helper function to dummy key entities.
   */
  protected function generateKeys() {
    $this->container->get('entity_type.manager')->getStorage('key')->create([
      'id' => 'my_key',
      'label' => 'My Key',
      'key_provider_settings' => [
        'key_value' => 'Super secret value',
      ],
    ])->save();
    $this->container->get('entity_type.manager')->getStorage('key')->create([
      'id' => 'second_key',
      'label' => 'Second Key',
      'key_provider_settings' => [
        'key_value' => 'Yet another key',
      ],
    ])->save();
  }

  /**
   * Tests that KeyProvider::hasKeyRepository() returns TRUE.
   */
  public function testHasKeyRepositoryIsTrue() {
    $this->assertTrue($this->container->get('akamai.key_provider')->hasKeyRepository());
  }

  /**
   * Tests that the key provider retrieves keys from key module.
   */
  public function testKeyProviderCanGetKeys() {
    $keys = $this->container->get('akamai.key_provider')->getKeys();
    $this->assertEquals($keys['my_key']->label(), 'My Key');
    $this->assertEquals($keys['my_key']->getKeyValue(), 'Super secret value');
    $this->assertEquals($keys['second_key']->label(), 'Second Key');
    $this->assertEquals($keys['second_key']->getKeyValue(), 'Yet another key');
  }

  /**
   * Tests that KeyProvider::getKey() retrieves specific key.
   */
  public function testCanGetSpecificKey() {
    $this->assertEquals($this->container->get('akamai.key_provider')->getKey('second_key'), 'Yet another key');
  }

  /**
   * Tests that KeyProvider::getKey() retrieves specific key.
   */
  public function testInvalidKeyIsNull() {
    $this->assertNull($this->container->get('akamai.key_provider')->getKey('some_invalid_key'));
  }

}
