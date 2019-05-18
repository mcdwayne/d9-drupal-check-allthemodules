<?php

namespace Drupal\Tests\feeds_para_mapper\Unit;


use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds_para_mapper\Mapper;
use Drupal\Tests\feeds_para_mapper\Unit\Helpers\Common;
use Drupal\Tests\feeds_para_mapper\Unit\Helpers\FieldConfig;


/**
 * @group Feeds Paragraphs
 * @coversDefaultClass \Drupal\feeds_para_mapper\Mapper
 */
class TestMapper extends FpmTestBase
{
  use Common;
  /**
   * @var string
   */
  protected $class;
  /**
   * @var string
   */
  protected $type;
  /**
   * @var Mapper
   */
  protected $mapper;

  /**
   * @inheritdoc
   */
  protected function setUp()
  {
    $this->class = Text::class;
    $this->type  = "text";
    parent::setUp();
    $this->addServices($this->services);
    $this->mapper = $this->getMapperObject();
  }

  /**
   * @covers ::getTargets
   */
  public function testGetTargets(){
    $targets = $this->mapper->getTargets('node','product');
    $message = "Targets found";
    self::assertTrue(count($targets) > 0, $message);
    $targets = $this->mapper->getTargets('test','test');
    $message = "Returns empty array if there are no targets";
    self::assertTrue(is_array($targets) && count($targets) === 0, $message);
  }

  /**
   *
   * @covers ::findParagraphsFields
   */
  public function testFindParagraphsFields(){
    $fields = $this->mapper->findParagraphsFields('node','product');
    $message = "Paragraphs fields found";
    $isFound = count($fields) && $fields[0]->getType() === "entity_reference_revisions";
    self::assertTrue($isFound, $message);
    $fields = $this->mapper->findParagraphsFields('test','test');
    $message = "Returns empty array if no paragraphs fields found";
    self::assertTrue(is_array($fields) && count($fields) === 0, $message);
  }

  /**
   *
   * @covers ::getSubFields
   */
  public function testGetSubFields(){
    // Test against a paragraph field:
    $field = $this->fields[0]->reveal();
    $subFields = $this->mapper->getSubFields($field);
    $targetInfo = $subFields[0]->get('target_info');
    $message = "TargetInfo object is attached to the target field";
    self::assertTrue(isset($targetInfo), $message);
    $notParagraph = $subFields[0]->getType() !== "entity_reference_revisions";
    $message = "Field type not paragraph";
    self::assertTrue($notParagraph, $message);
    // Test against non-paragraph field:
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $subFields = $this->mapper->getSubFields($field);
    $message = "Returns array if no sub-fields found";
    self::assertTrue(is_array($subFields) && count($subFields) === 0, $message);
  }

  /**
   *
   * @covers ::updateInfo
   */
  public function testUpdateInfo(){
    // Test against a field with TargetInfo attached:
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $this->mapper->updateInfo($field,'path', array('test' => 'test value'));
    $info = $field->get('target_info');
    $message = "TargetInfo property is updated";
    self::assertTrue(count($info->path) === 1 && $info->path['test'] === 'test value', $message);

    // Test against a field with no TargetInfo attached:
    $field->set('target_info', null);
    $this->mapper->updateInfo($field,'path', array('test' => 'test value 2'));
    $info = $field->get('target_info');
    $message = "TargetInfo property is updated";
    self::assertTrue(count($info->path) === 1 && $info->path['test'] === 'test value 2', $message);
    // Test with non-existing property:
    $res = $this->mapper->updateInfo($field, 'test','test');
    $message = "should return false with non-existing property";
    self::assertFalse($res, $message);
  }
  /**
   *
   * @covers ::getInfo
   */
  public function testGetInfo(){
    // Test against a field with TargetInfo attached:
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $info = $this->getTargetInfo();
    $info->plugin = array();
    $field->set('target_info', $info);
    $res = $this->mapper->getInfo($field,'plugin');
    self::assertNotNull($res, "property should exist");
    $res = $this->mapper->getInfo($field,'test');
    self::assertNull($res, "property should not exist");
    // Test against a field with no TargetInfo attached:
    $field->set('target_info', null);
    $res = $this->mapper->getInfo($field,'test');
    self::assertNull($res, "Should return null if no TargetInfo attached to the field");
  }

  /**
   * @covers ::setFieldsInCommon
   */
  public function testSetFieldsInCommon(){
    $conf = new FieldConfig(
      'Bundle two field two',
      'bundle_two_field_two',
      'text',
      4,
      1,
      array(
        'handler_settings' => array(),
      ),
      array(),
      'paragraph',
      'bundle_two',
      'bundle_one_bundle_two',
      3
    );
    $path = array(
      array(
        'bundle' => 'bundle_one',
        'host_field' => 'paragraph_field',
        'host_entity' => 'node',
        'order' => 0,
      ),
    );
    $firstTargetInfo = $this->getTargetInfo();
    $firstTargetInfo->path = $path;
    $secondTargetInfo = $this->getTargetInfo();
    $secondTargetInfo->path = $path;
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $field->set('target_info', $firstTargetInfo);
    $second_field = $this->fieldHelper->getField($conf)->reveal();
    $second_field->set('target_info', $secondTargetInfo);
    $fields = array($second_field);
    $method = $this->getMethod(Mapper::class,'setFieldsInCommon');
    $method->invokeArgs($this->mapper,array(&$field, &$fields));
    $inCommonExists = count($firstTargetInfo->in_common) && count($secondTargetInfo->in_common);
    $message = "in common field are added for both fields";
    self::assertTrue($inCommonExists, $message);
    $first_field_name = $field->getName();
    $second_field_name = $second_field->getName();
    $field_two_has_one = $secondTargetInfo->in_common[0]['name'] === $first_field_name;
    self::assertTrue($field_two_has_one, "First field has the second in common");
    $field_one_has_two = $firstTargetInfo->in_common[0]['name'] === $second_field_name;
    self::assertTrue($field_one_has_two, "Second field has the first in common");
  }

  /**
   *
   * @covers ::buildPath
   *
   */

  public function testBuildPath(){
    $method = $this->getMethod(Mapper::class,'buildPath');
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $first_host = array(
      'bundle' => 'bundle_one',
      'host_field' => 'paragraph_field',
      'host_entity' => 'node',
      'order' => 0,
    );
    $path = $method->invokeArgs($this->mapper,array($field, $first_host));
    self::assertTrue(is_array($path) && count($path), "Field path exists");
    $bundles = $this->fieldHelper->bundles;
    $bundles = array_values($bundles);
    for($i = 0; $i < count($bundles); $i++){
      self::assertTrue($path[$i]['bundle'] === $bundles[$i], "bundle exists in the path");
    }
  }

  /**
   * @covers ::getMaxValues
   */
  public function testGetMaxValues(){
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    // Cardinality is -1:
    $expected = (int) $field->getFieldStorageDefinition()->getCardinality();
    $max = $this->mapper->getMaxValues($field);
    self::assertSame($expected, $max);
    $max = $this->mapper->getMaxValues($field, array('max_values' => 10));
    self::assertSame(10, $max);
    // Invalid input should return the field cardinality:
    $max = $this->mapper->getMaxValues($field, array('max_values' => -2));
    self::assertSame($expected, $max);
    $field->set('cardinality','2');
    $max = $this->mapper->getMaxValues($field, array('max_values' => -2));
    self::assertSame( 2, $max);
    $max = $this->mapper->getMaxValues($field, array('max_values' => 3));
    self::assertSame( 2, $max);
  }
}