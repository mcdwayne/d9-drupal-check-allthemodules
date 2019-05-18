<?php

namespace Drupal\Tests\sendwithus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;
use Drupal\sendwithus\ApiManager;
use sendwithus\API;

/**
 * ApiManager kernel tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\ApiManager
 */
class ApiManagerTest extends KernelTestBase {

  public static $modules = ['sendwithus', 'key'];

  /**
   * @covers ::__construct
   * @covers ::getKeyName
   * @covers ::setApiKey
   * @covers ::getApiKey
   * @covers ::getAdapter
   */
  public function testDefault() {
    foreach (['sendwithus', 'sendwithus2'] as $i => $value) {
      $key = Key::create([
        'id' => $value,
        'label' => $value,
      ]);
      $key->setKeyValue(123 + $i);
      $key->save();
    }

    $sut = new ApiManager($this->container->get('key.repository'), $this->container->get('config.factory'));
    $this->assertEquals(NULL, $sut->getApiKey());
    $this->config('sendwithus.settings')->set('api_key', 'sendwithus')->save();

    // Make sure we get proper api key when we set the api key.
    $sut = new ApiManager($this->container->get('key.repository'), $this->container->get('config.factory'));
    $this->assertEquals(123, $sut->getApiKey());

    $this->assertInstanceOf(API::class, $sut->getAdapter());

    // Make sure we can change the api key.
    $sut->setApiKey('sendwithus2');
    $this->assertEquals(124, $sut->getApiKey());
  }

}
