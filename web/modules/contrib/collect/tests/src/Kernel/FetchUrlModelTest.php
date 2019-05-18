<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests features of the Collect Fetch Url model plugin.
 *
 * @group collect
 */
class FetchUrlModelTest extends KernelTestBase {

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
   * Tests the properties of the model plugin.
   */
  public function testProperties() {
    $container = Container::create([
      'data' => Json::encode([
        'request-headers' => ['Accept' => ['text/html']],
        'response-headers' => ['Content-Type' => ['text/html']],
        'body' => '<html><body>Body text</body></html>',
      ]),
      'schema_uri' => 'http://schema.md-systems.ch/collect/0.0.1/url',
      'type' => 'application/json',
    ]);

    $fetch_url_model_plugin = Model::create([
      'id' => 'collect_fetch_url',
      'label' => t('Fetch Url Model Plugin'),
      'uri_pattern' => 'http://schema.md-systems.ch/collect/0.0.1/url',
      'plugin_id' => 'collect_fetch_url',
    ]);
    $fetch_url_model_plugin->save();

    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $data = $typed_data_provider->getTypedData($container);

    // Each property of the model plugin should map to data in the container.
    $this->assertEqual('text/html', $data->get('accept')->getValue());
    $this->assertEqual('text/html', $data->get('content-type')->getValue());
    $this->assertEqual('<html><body>Body text</body></html>', $data->get('body_raw')->getValue());
    $this->assertEqual('Body text', $data->get('body_text')->getValue());
    // Existing property but non-existing value.
    $fetch_url_model_plugin->setTypedProperty('carrot', new PropertyDefinition('carrot', DataDefinition::create('string')))->save();
    $data = $typed_data_provider->getTypedData($container);
    $this->assertNull($data->get('carrot')->getValue());
  }
}
