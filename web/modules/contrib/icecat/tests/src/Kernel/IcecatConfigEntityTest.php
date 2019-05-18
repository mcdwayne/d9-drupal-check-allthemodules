<?php

namespace Drupal\Tests\icecate\Kernel;

use Drupal\icecat\Entity\IcecatMapping;
use Drupal\icecat\Entity\IcecatMappingLink;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the mapping entity.
 */
class IcecatConfigEntityTest extends EntityKernelTestBase {

  /**
   * Enable the required modules.
   *
   * @var array
   */
  public static $modules = [
    'icecat',
    'inline_entity_form',
  ];

  /**
   * Tests a basic mapping entity.
   *
   * Tests the config entity and its properties.
   */
  public function testBasicMapping() {
    $mapping = new IcecatMapping([
      'id' => 2,
      'label' => 'Demo mapping',
      'example_ean' => '0123456712345',
      'entity_type' => 'node',
      'entity_type_bundle' => 'article',
      'data_input_field' => 'field_ean',
    ]);
    $mapping->save();

    // Load the mapping.
    $loaded_mapping = IcecatMapping::load(2);

    // Test properties.
    $this->assertEquals('Demo mapping', $loaded_mapping->label());
    $this->assertEquals('0123456712345', $loaded_mapping->getExampleEans());
    $this->assertEquals('field_ean', $loaded_mapping->getDataInputField());
    $this->assertEquals('node', $loaded_mapping->getMappingEntityType());
    $this->assertEquals('article', $loaded_mapping->getMappingEntityBundle());

    // Check with multiple ean codes.
    $loaded_mapping->set('example_ean', '123,456');
    $loaded_mapping->save();

    $loaded_mapping = IcecatMapping::load(2);
    $this->assertEquals(['123', '456'], $loaded_mapping->getExampleEanList());
  }

  /**
   * Tests the icecat mapping link config entity.
   */
  public function testIcecatMappingLink() {
    $mapping = new IcecatMapping([
      'id' => 2,
      'label' => 'Demo mapping',
      'example_ean' => '0123456712345',
      'entity_type' => 'node',
      'entity_type_bundle' => 'article',
      'data_input_field' => 'field_ean',
    ]);
    $mapping->save();
    $mapping_link = new IcecatMappingLink([
      'id' => 5,
      'local_field' => 'field_demo',
      'remote_field' => 'demoField',
      'remote_field_type' => 'text',
      'mapping' => 2,
    ]);
    $mapping_link->save();

    // Load the mapping link from the database.
    $loaded_mapping_link = IcecatMappingLink::load(5);

    // Test properties.
    $this->assertEquals('field_demo::demoField', $loaded_mapping_link->label());
    $this->assertEquals('field_demo', $loaded_mapping_link->getLocalField());
    $this->assertEquals('demoField', $loaded_mapping_link->getRemoteField());
    $this->assertEquals('text', $loaded_mapping_link->getRemoteFieldType());

    // @todo: check the mapping itself.
  }

}
