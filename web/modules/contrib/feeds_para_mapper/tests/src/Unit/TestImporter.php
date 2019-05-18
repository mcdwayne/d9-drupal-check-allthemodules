<?php

namespace Drupal\Tests\feeds_para_mapper\Unit;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds_para_mapper\Importer;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\feeds_para_mapper\Unit\Helpers\Common;
use Prophecy\Argument;

/**
 * @group Feeds Paragraphs
 * @coversDefaultClass \Drupal\feeds_para_mapper\Importer
 */
class TestImporter extends FpmTestBase
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
   * @var Importer
   */
  protected $importer;

  /**
   * @var FieldDefinitionInterface
   */
  protected $field;
  /**
   * @inheritdoc
   */
  protected function setUp()
  {
    $this->class = Text::class;
    $this->type  = "text";
    parent::setUp();
    $this->addServices($this->services);
    $entity_manager = $this->entityHelper->getEntityTypeManagerMock()->reveal();
    $field_manager = $this->fieldHelper->getEntityFieldManagerMock();
    $mapper = $this->getMapperObject();
    try {
      $this->importer = new Importer($entity_manager, $field_manager, $mapper);
    } catch (\Exception $e) {
    }
    $targets = $mapper->getTargets('node', 'products');
    $this->field = $targets[0];
    $this->initImporter();
  }
  protected function initImporter(){
    $propsValues = array(
      'feed'          => $this->getFeedMock(),
      'entity'        => $this->node->reveal(),
      'target'        => $this->field,
      'configuration' => array('max_values' => 1),
      'values'        => array( array('value' => "Test value")),
      'targetInfo'    => $this->field->get('target_info'),
      'instance'      => $this->wrapperTarget->createTargetInstance(),
    );
    foreach ($propsValues as $prop => $value) {
      $this->updateProperty(Importer::class, $this->importer,$prop, $value);
    }
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct(){
    $entity_manager = $this->entityHelper->getEntityTypeManagerMock();
    $field_manager = $this->fieldHelper->getEntityFieldManagerMock();
    $mapper = $this->getMapperObject();
    // Get mock, without the constructor being called
    $mock = $this->getMockBuilder(Importer::class)
      ->disableOriginalConstructor()
      ->getMock();
    $reflectedClass = new \ReflectionClass(Importer::class);
    $constructor = $reflectedClass->getConstructor();
    // Force the constructor to throw error:
    $entity_manager->getStorage('paragraph')->willThrow(InvalidPluginDefinitionException::class);
    // now call the constructor
    $exception = null;
    try{
      $constructor->invoke($mock, $entity_manager->reveal(), $field_manager, $mapper);
    }catch (\Exception $e){
      $exception = $e;
    }
    $entity_manager->getStorage('paragraph')->shouldHaveBeenCalled();
    self::assertInstanceOf(InvalidPluginDefinitionException::class, $exception);
  }
  /**
   * @covers ::import
   */
  public function testImport(){
    $this->entityHelper->values = array();
    $feed = $this->getFeedMock();
    $entity = $this->entityHelper->node;
    $config = array(
      'max_values' => 1,
    );
    $values = array(
      array(
        'value' => "Test value",
      ),
    );
    $instance = $this->wrapperTarget->createTargetInstance();
    $this->importer->import($feed, $entity->reveal(), $this->field, $config, $values, $instance);
    $this->instanceMock->setTarget(
      Argument::type(FeedInterface::class),
      Argument::type(Paragraph::class),
      Argument::type('string'),
      Argument::type('array')
    )->shouldHaveBeenCalled();
  }

  /**
   * @covers ::setValue
   */
  public function testSetValue(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'setValue');
    $paragraph = end($this->entityHelper->paragraphs);
    $value = array('value' => "a");
    $args = array($paragraph->reveal(), $value);
    $method->invokeArgs($this->importer, $args);
    $this->instanceMock->setTarget(
      Argument::type(FeedInterface::class),
      Argument::type(Paragraph::class),
      Argument::type('string'),
      Argument::type('array')
    )->shouldHaveBeenCalled();
    $appendedValue = $this->entityHelper->values['bundle_two_text'];
    self::assertSame($value, $appendedValue, "The value has been set");
  }

  /**
   * @covers ::appendToUpdate
   */
  public function testAppendToUpdate(){
    // Tests adding update
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'appendToUpdate');
    $paragraph = end($this->entityHelper->paragraphs);
    $args = array($paragraph->reveal());
    $method->invokeArgs($this->importer, $args);
    $target = $this->getProperty($this->importer,'target');
    $paragraphs = $target->target_info->paragraphs;
    self::assertCount(1, $paragraphs,'the target info contains 1 paragraph to update');
    $entity = $this->getProperty($this->importer,'entity');
    $fpm_targets = $entity->fpm_targets;
    self::assertArrayHasKey('bundle_two_text', $fpm_targets, 'bundle_two_text exists in the updates list');
    $toUpdate = $fpm_targets['bundle_two_text'];
    self::assertInstanceOf(FieldConfigInterface::class, $toUpdate, 'bundle_two_text is FieldConfigInterface');
    // Test appending to an exists updates:
    $method->invokeArgs($this->importer, $args);
    $entity = $this->getProperty($this->importer,'entity');
    $paragraphs = $entity->fpm_targets['bundle_two_text']->target_info->paragraphs;
    self::assertCount(2, $paragraphs, 'Another paragraph is added for update');
  }
  /**
   * @covers ::initHostParagraphs
   */
  public function testInitHostParagraphs(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'initHostParagraphs');
    $result = $method->invoke($this->importer);
    foreach ($result as $item) {
      $paragraph = $item['paragraph'];
      $host_info = $paragraph->host_info;
      self::assertNotNull($host_info);
      self::assertTrue(count($host_info) === 4, "The info array should contain 4 items");
      $value = $item['value'];
      self::assertTrue(count($value) > 0, "The value key contains values");
    }
  }

  /**
   *
   * @covers ::getTarget
   */
  public function testGetTarget(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'getTarget');
    $any = Argument::any();
    $str = Argument::type('string');
    $paragraph = $this->prophesize(Paragraph::class);
    $paragraph->hasField($any)->willReturn(true);
    $values = array(array('entity' => $paragraph->reveal()));
    $this->node->hasField($str)->willReturn(true);
    $fieldItem = $this->prophesize(EntityReferenceRevisionsFieldItemList::class);
    $fieldItem->getValue()->willReturn($values);
    $this->node->get($str)->willReturn($fieldItem->reveal());
    $paragraph->get($str)->willReturn($fieldItem->reveal());
    $args = array($this->node->reveal(), $this->field);
    // Call getTarget:
    $result = $method->invokeArgs($this->importer,$args);
    self::assertNotEmpty($result, "Result not empty");
    foreach ($result as $item) {
      self::assertInstanceOf(Paragraph::class, $item, "The result item is paragraph");
    }
    // Test with non-nested field:
    $info = $this->field->get('target_info');
    $info->path = array(
      array (
        'bundle' => 'bundle_one',
        'host_field' => 'paragraph_field',
        'host_entity' => 'node',
        'order' => 0,
      ),
    );
    $this->field->set('target_info', $info);
    $args = array($paragraph->reveal(), $this->field);
    // Call getTarget:
    $result = $method->invokeArgs($this->importer,$args);
    self::assertNotEmpty($result, "Result not empty");
    foreach ($result as $item) {
      self::assertInstanceOf(Paragraph::class, $item, "The result item is paragraph");
    }
  }

  /**
   * @covers ::loadTarget
   */
  public function testLoadTarget(){
    $result = $this->importer->loadTarget($this->node->reveal(), $this->field);
    self::assertNotEmpty($result,"nested entities loaded");
    // Test with non-nested field:
    $info = $this->field->get('target_info');
    $info->path = array(
      array (
        'bundle' => 'bundle_one',
        'host_field' => 'paragraph_field',
        'host_entity' => 'node',
        'order' => 0,
      ),
    );
    $this->field->set('target_info', $info);
    $result = $this->importer->loadTarget($this->node->reveal(), $this->field);
    self::assertNotEmpty($result,"flat entity loaded");
  }

  /**
   * @covers ::createParagraphs
   */
  public function testCreateParagraphs(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'createParagraphs');
    $values = array(array('a'), array('b'), array('c'));
    $args = array($this->node->reveal(), $values);
    $result = $method->invokeArgs($this->importer, $args);
    self::assertCount(3, $result);
    for ($i = 0; $i < count($result); $i++) {
      self::assertArrayEquals($values[$i], $result[$i]['value']);
      self::assertInstanceOf(Paragraph::class, $result[$i]['paragraph']);
    }
  }

  /**
   * @covers ::updateParagraphs
   */
  public function testUpdateParagraphs(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'updateParagraphs');
    $values = array(
      array(array('value' => 'a')),
      array(array('value' => 'b')),
      array(array('value' => 'c')),
    );
    $paragraphs = $this->entityHelper->paragraphs;
    $lastPar = end($paragraphs);
    $args = array(array($lastPar->reveal()), $values);
    $result = $method->invokeArgs($this->importer, $args);
    self::assertCount(1, $result);
    for ($i = 0; $i < count($result); $i++) {
      self::assertArrayEquals($values[$i], $result[$i]['value']);
      self::assertInstanceOf(Paragraph::class, $result[$i]['paragraph']);
      self::assertArrayHasKey('state', $result[$i]);
    }
  }

  /**
   * @covers ::appendParagraphs
   */
  public function testAppendParagraphs(){
    $this->entityHelper->values = array(
      'bundle_two_text' => array(
        array(
          'value' => 'a'
        ),
      ),
    );
    $method = $this->getMethod(Importer::class,'appendParagraphs');
    $values = array(
      array(array('value' => 'a')),
      array(array('value' => 'b')),
      array(array('value' => 'c')),
      );
    $paragraphs = array_values($this->entityHelper->paragraphs);
    $paragraph = $paragraphs[1]->reveal();
    $paragraph->host_info = array(
      'field' => 'bundle_one_bundle_two',
      'bundle' => 'bundle_two',
      'entity' => $paragraphs[0]->reveal(),
      'type' => 'paragraph',
    );
    $args = array(array($paragraph), $values);
    $result = $method->invokeArgs($this->importer, $args);
    self::assertCount(3, $result);
    for ($i = 0; $i < count($result); $i++) {
      self::assertArrayEquals($values[$i], $result[$i]['value']);
      self::assertInstanceOf(Paragraph::class, $result[$i]['paragraph']);
      self::assertArrayHasKey('state', $result[$i]);
      $host_info = $result[$i]['paragraph']->host_info;
      self::assertArrayEquals($paragraph->host_info, $host_info);
    }
  }

  /**
   * @covers ::createParents
   */
  public function testCreateParents(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'createParents');
    $node = $this->node->reveal();
    $expected = array(
      'type' => 'node',
      'entity' => $node,
      'bundle' => 'bundle_one',
      'field' => 'paragraph_field',
    );
    $result = $method->invokeArgs($this->importer, array($node));
    self::assertSame($expected, $result->host_info);
    // Test with already created parents:
    $result = $method->invokeArgs($this->importer, array($node));
    self::assertNull($result);
  }

  /**
   * @covers ::duplicateExisting
   */
  public function testDuplicateExisting(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'duplicateExisting');
    $paragraph = $this->entityHelper->paragraphs[2];
    $paragraph->isNew()->willReturn(false);
    $parObject = $paragraph->reveal();
    $result = $method->invokeArgs($this->importer, array($parObject));
    $paragraph->isNew()->shouldHaveBeenCalled();
    $paragraph->getParentEntity()->shouldHaveBeenCalled();
    $paragraph->getType()->shouldHaveBeenCalled();
    $paragraph->getParentEntity()->shouldHaveBeenCalled();
    $paragraph->get('parent_field_name')->shouldHaveBeenCalled();
    self::assertInstanceOf(Paragraph::class, $result);
  }

  /**
   * @covers ::removeExistingParents
   */
  public function testRemoveExistingParents(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'removeExistingParents');
    $path = $this->field->get('target_info')->path;
    $result = $method->invokeArgs($this->importer, array($path));
    self::assertArrayHasKey('parents', $result, 'parents key exists');
    self::assertArrayHasKey('removed', $result, 'removed key exists');
    self::assertCount(count($path), $result['parents'], 'parents count is correct');
    self::assertCount(0, $result['removed'], 'removed array is empty');
    // check that the order of each parent is correct
    for($i=1; $i < count($result['parents']); $i++){
      self::assertTrue($result['parents'][$i]['order'] > $result['parents'][$i -1]['order'], 'Parents order is correct');
    }
    $this->entityHelper->values['paragraph_field'] = array(
      array(
        'entity' => $this->entityHelper->paragraphs[1]
      )
    );
    $result = $method->invokeArgs($this->importer, array($path));
    self::assertCount(count($path) -1, $result['parents'], 'parents count is correct');
    self::assertCount(1, $result['removed'], 'removed is not empty');
  }

  /**
   * @covers ::createParagraph
   */
  public function testCreateParagraph(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'createParagraph');
    $node = $this->node->reveal();
    $args = array(
      $field = "paragraph_field",
      $bundle = "bundle_one",
      $node,
    );
    $result = $method->invokeArgs($this->importer, $args);
    $value = $this->entityHelper->values['paragraph_field'];
    self::assertTrue(isset($value[0]['entity']), 'the host entity has the created paragraph');
    self::assertInstanceOf(Paragraph::class, $result);
    $host_info = $result->host_info;
    $keys = array(
      'type',
      'entity',
      'bundle',
      'field'
    );
    foreach ($keys as $key) {
      self::assertArrayHasKey($key, $host_info);
    }
  }

  /**
   * @covers ::shouldCreateNew
   */
  public function testShouldCreateNew(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'shouldCreateNew');
    $paragraph = end($this->entityHelper->paragraphs)->reveal();
    $parent_values = array(
      array(
        'value' => '2',
      ),
      array(
        'value' => '2',
      ),
    );
    $target_values = array(
      array(
        'value' => 'a',
      ),
      array(
        'value' => 'b',
      ),
      array(
        'value' => 'c',
      ),
    );
    $args = array(
      $paragraph,
      array(
        array(array('value' => 'a')),
        array(array('value' => 'b')),
        array(array('value' => 'c')),
      ),
    );

    // Slices: 3
    // Host entity values: 0
    // target field values: 0
    // Max allowed values: 1
    // Based on this we should not create new entity, instead fill the existing:
    $result = $method->invokeArgs($this->importer, $args);
    self::assertFalse($result, 'we should NOT create new paragraph entities');

    $this->entityHelper->values['bundle_one_bundle_two'] = $parent_values;
    $this->entityHelper->values['bundle_two_text'] = $target_values;
    $this->updateProperty(Importer::class,$this->importer,'configuration', array('max_values' => 4));

    // Slices: 3
    // Host entity values: 2
    // target field values: 3
    // Max allowed values: 4
    // We should not create entity:
    $result = $method->invokeArgs($this->importer, $args);
    self::assertFalse($result, 'we should NOT create new paragraph entities');

    $target_values+= array('value' => 'd');
    $this->entityHelper->values['bundle_two_text'] = $target_values;

    // Slices: 3
    // Host entity values: 2
    // target field values: 4
    // Max allowed values: 4
    // We should create another entity:
    $result = $method->invokeArgs($this->importer, $args);
    self::assertTrue($result, 'we should create new paragraph entities');
  }

  /**
   * @covers ::checkValuesChanges
   */
  public function testCheckValuesChanges(){
    $this->entityHelper->values = array();
    $method = $this->getMethod(Importer::class,'checkValuesChanges');
    $paragraph = end($this->entityHelper->paragraphs)->reveal();
    $args = array(
      array(
        array(array('value' => 'a')),
        array(array('value' => 'b')),
        array(array('value' => 'c')),
      ),
      array($paragraph)
    );
    $target_values = array(
      array(
        'value' => 'a',
      ),
      array(
        'value' => 'b',
      ),
      array(
        'value' => 'c',
      ),
    );
    $this->entityHelper->values['bundle_two_text'] = $target_values;
    $result = $method->invokeArgs($this->importer, $args);
    self::assertCount(3, $result);
    self::assertTrue($result[0]['state'] === 'unchanged');
    $args[0][0][0]['value'] = "d";
    $result = $method->invokeArgs($this->importer, $args);
    self::assertTrue($result[0]['state'] === 'changed');
    $args[0][0][0]['value'] = "a";
    $args[0][0][0]['sub_field'] = "d";
    $result = $method->invokeArgs($this->importer, $args);
    self::assertTrue($result[0]['state'] === 'changed');
  }
}