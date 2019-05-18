<?php

namespace Drupal\Tests\kong\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\kong\Plugin\KongObjectInterface;
use Drupal\kong\Plugin\KongObjectManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\kong\Plugin\KongObjectManager
 * @group kong
 */
class KongObjectManagerTest extends UnitTestCase {

  /**
   * Returns an array for test argument.
   *
   * @return array
   *   The array for test argument.
   */
  public static function booleanProvider() {
    return [
      [FALSE],
      [TRUE],
    ];
  }

  /**
   * @covers ::__construct
   * @covers ::createInstance
   * @dataProvider booleanProvider
   *
   * @param bool $case
   *   The test case.
   */
  public function test(bool $case) {
    $namespaces = $this->prophesize(\ArrayObject::class);
    $namespaces->getIterator()
      ->willReturn($this->prophesize(\ArrayIterator::class));
    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    if ($case) {
      $cache = new \stdClass();
      $cache->data = [
        'test' => [
          'class' => KongObjectStub::class,
        ],
      ];
      $cache_backend->get('kong_object_plugins')->willReturn($cache);

      \Drupal::setContainer($this->prophesize(ContainerInterface::class)
        ->reveal());
    }
    else {
      self::expectException(PluginException::class);
    }

    $plugin_manager = new KongObjectManager(
      $namespaces->reveal(),
      $cache_backend->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->getConfigFactoryStub([
        'kong.settings' => [
          'base_uri' => 'http://localhost:8001',
        ],
      ])
    );

    self::assertInstanceOf(KongObjectInterface::class, $plugin_manager->createInstance('test'));
  }

}

/**
 * Class KongObjectStub.
 */
class KongObjectStub implements KongObjectInterface {

  /**
   * {@inheritdoc}
   */
  public function add(array $data) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters = [], bool $count = FALSE) {
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, array $data) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
  }

}
