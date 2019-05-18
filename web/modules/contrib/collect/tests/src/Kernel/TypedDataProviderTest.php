<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Typed Data provider service.
 *
 * @group collect
 */
class TypedDataProviderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'hal',
    'rest',
    'serialization',
    'collect_common',
  ];

  /**
   * The typed data provider to test.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('collect_container');
    $this->typedDataProvider = \Drupal::service('collect.typed_data_provider');
  }

  /**
   * Tests the getTypedData() method.
   */
  public function testGetTypedData() {
    // The container should be available as the '_container' property.
    $empty_container = Container::create();
    $typed_data = $this->typedDataProvider->getTypedData($empty_container);
    $this->assertTrue($typed_data->getDataDefinition()->getPropertyDefinition(CollectDataInterface::CONTAINER_KEY) instanceof EntityDataDefinitionInterface, 'The "_container" property is defined as an entity.');
    $this->assertIdentical($empty_container, $typed_data->get(CollectDataInterface::CONTAINER_KEY)->getValue(), 'The "_container" property value is identical to the initial container.');
    $this->assertIdentical($empty_container, $typed_data->getContainer(), 'The initial container is returned by getContainer().');
  }

  /**
   * Tests the resolveDataUri() method.
   */
  public function testResolveDataUri() {
    // Create a container for Stockholm.
    $container = Container::create([
      'origin_uri' => 'https://www.wikidata.org/wiki/Q1754',
      'schema_uri' => 'http://schema.org/Place',
      'data' => Json::encode([
        'label' => 'Stockholm',
      ]),
    ]);
    $container->save();

    // Create a model for places.
    $model = Model::create([
      'id' => 'schemaorg_place',
      'uri_pattern' => 'http://schema.org/Place',
      'plugin_id' => 'json',
    ]);
    // Places have names.
    $model->setTypedProperty('name', new PropertyDefinition('label', DataDefinition::create('string')));
    $model->save();

    // The value should be returned for existing URI and property.
    $value = $this->typedDataProvider->resolveDataUri('https://www.wikidata.org/wiki/Q1754#name');
    $this->assertEqual('Stockholm', $value->getString());

    // Unknown URI should throw an exception.
    $message = 'Exception thrown for unknown container';
    try {
      $value = $this->typedDataProvider->resolveDataUri('https://www.wikidata.org/wiki/Q72#name');
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }

    // Existing container but unknown property should throw an exception.
    $message = 'Exception thrown for unknown property on existing container';
    try {
      $value = $this->typedDataProvider->resolveDataUri('https://www.wikidata.org/wiki/Q1754#country');
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }
  }

}
