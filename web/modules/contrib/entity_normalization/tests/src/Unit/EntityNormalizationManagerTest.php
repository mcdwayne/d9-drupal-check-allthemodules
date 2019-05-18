<?php

namespace Drupal\Tests\entity_normalization\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_normalization\EntityNormalizationManager;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Exception;
use ReflectionClass;

/**
 * @coversDefaultClass \Drupal\entity_normalization\EntityNormalizationManager
 * @group entity_normalization
 */
class EntityNormalizationManagerTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cacheBackend;

  /**
   * The entity normalization manager.
   *
   * @var \Drupal\entity_normalization\EntityNormalizationManager
   */
  protected $entityNormalizationManager;

  /**
   * @covers ::findDefinitions
   *
   * @dataProvider getTestsFromFixtures
   */
  public function testDefinitions($module, $keys, $tests) {
    $this->setupManagerWithDefinitions($module);

    $definitions = $this->entityNormalizationManager->getDefinitions();
    $this->assertEquals(count($keys), count($definitions), 'Expected ' . count($keys) . ' definitions.');
    $this->assertEmpty(array_diff(array_keys($definitions), $keys));

    foreach ($tests as $test) {
      $entity = $this->getMockedEntity($test['in']['entity'], $test['in']['bundle']);
      $config = $this->entityNormalizationManager->getEntityConfig($entity, $test['in']['format']);
      $this->assertSame($test['out']['id'], $config->getPluginId());
      $this->assertSame($test['out']['fields'], array_keys($config->getFields()));
    }
  }

  /**
   * Setup a new EntityNormalizationManager with the given fixture name.
   *
   * @param string $fixture
   *   Name of the fixture to use.
   */
  private function setupManagerWithDefinitions($fixture) {
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $this->moduleHandler->getModuleDirectories()->willReturn(
      [$fixture => $this->fetchFixtureDirectory() . '/' . $fixture]
    );
    $this->moduleHandler = $this->moduleHandler->reveal();
    $this->cacheBackend = $this->cacheBackend->reveal();

    $this->entityNormalizationManager = new EntityNormalizationManager($this->moduleHandler, $this->cacheBackend);

    // The entity normalization manager uses static caching,
    // so make sure it's empty each run.
    $reflector = new ReflectionClass(EntityNormalizationManager::class);
    $property = $reflector->getProperty('cache');
    $property->setAccessible(TRUE);
    $property->setValue($this->entityNormalizationManager, []);
  }

  /**
   * Build a mocked entity based on the type and bundle.
   *
   * @param string $type
   *   One of 'node', 'term', 'user'.
   * @param string $bundle
   *   Entity bundle.
   *
   * @return \Drupal\node\NodeInterface
   *   The mocked entity.
   */
  private function getMockedEntity($type, $bundle) {
    $entity = NULL;
    switch ($type) {
      case 'node':
        $entity = $this->prophesize(NodeInterface::class);
        $entity->getEntityTypeId()->willReturn('node');
        break;

      case 'term':
        $entity = $this->prophesize(TermInterface::class);
        $entity->getEntityTypeId()->willReturn('taxonomy_term');
        break;

      case 'user':
        $entity = $this->prophesize(UserInterface::class);
        $entity->getEntityTypeId()->willReturn('user');
        break;
    }
    $entity->bundle()->willReturn($bundle);
    return $entity->reveal();
  }

  /**
   * Fetch all tests from the fixtures directory.
   *
   * Expected per test: a 'test.json' file and a normalization yml definition.
   *
   * @return array
   *   A list of tests from the fixtures.
   *
   * @throws \Exception
   *   Thrown when we don't find any tests.
   */
  public function getTestsFromFixtures() {
    $dir = $this->fetchFixtureDirectory() . '/*';
    $glob = glob($dir, GLOB_ONLYDIR);
    if ($glob === FALSE) {
      throw new Exception('Couldn\'t read in directory: ' . $dir);
    }

    $directories = array_filter($glob, function ($dir) {
      return is_dir($dir);
    });

    $tests = [];
    foreach ($directories as $directory) {
      $fileName = $directory . '/test.json';
      if (file_exists($fileName)) {
        $json = json_decode(file_get_contents($fileName), TRUE);
        if ($json) {
          $tests[] = $json;
        }
      }
    }
    if (empty($tests)) {
      throw new Exception('No tests found');
    }
    return $tests;
  }

  /**
   * Fetch the directory of the fixtures.
   *
   * @return string
   *   The directory of the fixtures.
   */
  private function fetchFixtureDirectory() {
    return __DIR__ . '/../../Fixtures';
  }

}
