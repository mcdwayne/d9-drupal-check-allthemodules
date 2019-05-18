<?php

namespace Drupal\Tests\eqsf\Kernel;


use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
/**
 * Unit tests for eqsf functions.
 *
 * @group eqsf
 */
class eqsfKernelTest extends FieldKernelTestBase {

  public static $modules = array(
    'field',
    'user',
    'entityqueue',
    'eqsf',
    'node',
    'datetime',
    'options',
    'system',
    'entity_test',
    'file'
  );

  public function setUp() {
    parent::setUp();

    // installSchema works
    $this->installSchema('eqsf', array('eq_schedule'));

    FieldStorageConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name'  => 'field_eq_test',
      'type'        => 'eqsf_field',
    ))->save();

    FieldConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name'  => 'field_eq_test',
      'bundle'      => 'entity_test',
    ))->save();
  }

  /**
   * Tests to add node.
   */

  public function testCreateNode() {

    // if succeeded not shown
    $entity = EntityTest::create();
    $this->assertFalse($entity->field_eq_test[0] instanceof FieldItemInterface, 'field_eq_test - Field implements interface. FALSE');

// @TODO Dit deel werkt niet -------------------------------
    //['pop' => 'u', 'la' => 'ted']
    $entity->field_eq_test->select = 1; //['pop' => 'u', 'la' => 'ted'];

    $entity->field_eq_test->startdate = '1234';
    $entity->field_eq_test->enddate = strtotime("now + 10");
    $entity->name->value = $this->randomMachineName();
    $entity->save();
// -------------------------------------

    $entity = EntityTest::load($entity->id());

    $this->assertTrue($entity->field_eq_test instanceof FieldItemListInterface, 'field_eq_test - Field implements interface. TRUE');
    $this->assertTrue($entity->field_eq_test[0] instanceof FieldItemInterface, 'field_eq_test - Field item implements interface.');
    //$this->assertNull(!$entity->field_eq_test->startdate);
    $this->assertEquals($entity->field_eq_test->startdate, '1234');
  }
}
