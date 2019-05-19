<?php

namespace Drupal\simpleaddress\Tests;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;
use Drupal;
use Drupal\Core\Locale\CountryManager;


/**
 * Tests the new entity API for the simpleaddress field type.
 */
class SimpleAddressItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpleaddress');

  public static function getInfo() {
    return array(
      'name' => 'Simple address field item',
      'description' => 'Tests the new entity API for the simpleaddress field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create a simpleaddress field and instance for validation.
    entity_create('field_storage_config', array(
      'name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'simpleaddress',
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Tests using entity fields of the simple address field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = entity_create('entity_test', array());

    $properties = array(
      'streetAddress' => '102, Olive Grove',
      'addressLocality' => 'Swindon',
      'addressRegion' => 'Wiltshire',
      'postalCode' => 'SN25 9RT',
      'postOfficeBoxNumber' => 'P.O. Box 12345',
      'addressCountry' => 'GB',
    );

    foreach ($properties as $key => $value) {
      $entity->field_test->{$key} = $value;
    }

    $entity->name->value = $this->randomName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');

    foreach ($properties as $key => $value) {
      $this->assertEqual($entity->field_test->{$key}, $value);
      $this->assertEqual($entity->field_test[0]->{$key}, $value);
    }

    // Verify changing the field value.
    $countries = \Drupal::service('country_manager')->getList();
    foreach ($properties as $key => $value) {
      if($key == 'addressCountry') {
        $new_{$key} = array_rand($countries);
      }
      else {
        $new_{$key} = $this->randomString();
      }
      $entity->field_test->{$key} = $new_{$key};
      $this->assertEqual($entity->field_test->{$key}, $new_{$key});
    }

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    foreach ($properties as $key => $value) {
      $this->assertEqual($entity->field_test->{$key}, $new_{$key});
    }
  }

}
