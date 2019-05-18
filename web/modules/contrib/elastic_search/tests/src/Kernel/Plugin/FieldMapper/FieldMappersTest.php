<?php

namespace Drupal\Tests\elastic_search\Kernel\Plugin\FieldMapper;

use Drupal\KernelTests\KernelTestBase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * ElasticIndexGeneratorTest
 *
 * @group elastic_search
 */
class FieldMappersTest extends KernelTestBase {

  use MockeryPHPUnitIntegration;

  /**
   * @var array
   */
  public static $modules = [
    'elastic_search',
  ];

  /**
   * Test a child only index, which will return nothing
   */
  public function testIndexGeneratorChildOnly() {

    /** @var \Drupal\elastic_search\Plugin\FieldMapperManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.elastic_field_mapper_plugin');
    $definitions = $plugin_manager->getDefinitions();
    foreach ($definitions as $definition) {
      /** @var \Drupal\elastic_search\Plugin\FieldMapperInterface $instance */
      $instance = $plugin_manager->createInstance($definition['id']);
      //assert implements interface
      $this->assertInstanceOf(\Drupal\elastic_search\Plugin\FieldMapperInterface::class, $instance);
      static::assertEquals(TRUE, is_array($instance->getSupportedTypes()));
      static::assertEquals(TRUE, is_array($instance->getFormFields([])));

    }

  }

}
