<?php
namespace Drupal\Tests\feeds_para_mapper\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds_para_mapper\Importer;
use Drupal\feeds_para_mapper\RevisionHandler;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\feeds_para_mapper\Unit\Helpers\Common;
use Prophecy\Argument;

/**
 * @group Feeds Paragraphs
 * @coversDefaultClass \Drupal\feeds_para_mapper\RevisionHandler
 */
class TestRevisionHandler extends FpmTestBase
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

  protected function setUp()
  {
    $this->class = Text::class;
    $this->type  = "text";
    parent::setUp();
    $this->addServices($this->services);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct(){
// Get mock, without the constructor being called
    $mock = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->getMock();
    $reflectedClass = new \ReflectionClass(RevisionHandler::class);
    $constructor = $reflectedClass->getConstructor();
    // Force the constructor to throw error:
    // now call the constructor
    $importer = $this->prophesize(Importer::class);
    $constructor->invoke($mock, $this->messenger->reveal(), $importer->reveal());
    $props = $reflectedClass->getProperties();
    $initialized = array(
      'messenger',
      'importer'
    );
    foreach ($props as $prop) {
      if(in_array($prop->getName(), $initialized)){
        $prop->setAccessible(true);
        $val = $prop->getValue($mock);
        self::assertNotEmpty($val);
      }
    }
  }

  /**
   * @covers ::handle
   */
  public function testHandle(){
    $field = end($this->fields)->reveal();
    $info = $this->getTargetInfo();
    $field->set('target_info', $info);
    $fpm_targets = array();
    $fpm_targets[$field->getName()] = $field;
    $node = $this->node->reveal();
    $node->fpm_targets = $fpm_targets;
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(['checkUpdates','cleanUp'])->getMock();
    $revHandler->expects($this->atLeastOnce())->method('checkUpdates');
    $revHandler->expects($this->atLeastOnce())->method('cleanUp');
    $revHandler->handle($node);
  }

  /**
   * @covers ::checkUpdates
   */
  public function testCheckUpdates(){
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(['createRevision'])->getMock();
    $revHandler->expects($this->atLeastOnce())->method('createRevision');
    $method = $this->getMethod($revHandler,'checkUpdates');
    $paragraph = end($this->entityHelper->paragraphs);
    $paragraph->isNew()->willReturn(false);
    $method->invokeArgs($revHandler, array(array($paragraph->reveal())));
  }

  /**
   * @covers ::createRevision
   */
  public function testCreateRevision(){
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(array('updateParentRevision'))->getMock();
    $revHandler->expects($this->atLeastOnce())
      ->method('updateParentRevision')
      ->with($this->isInstanceOf(Paragraph::class));
    $method = $this->getMethod($revHandler,'createRevision');
    $paragraph = end($this->entityHelper->paragraphs);
    $bool = Argument::type('bool');
    $paragraph->setNewRevision($bool)->willReturn(null);
    $paragraph->isDefaultRevision($bool)->willReturn(null);
    $method->invoke($revHandler, $paragraph->reveal());
    $paragraph->setNewRevision($bool)->shouldHaveBeenCalled();
    $paragraph->isDefaultRevision($bool)->shouldHaveBeenCalled();
    $paragraph->save()->shouldHaveBeenCalled();
  }

  /**
   * @covers ::updateParentRevision
   */
  public function testUpdateParentRevision(){
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()->getMock();
    $method = $this->getMethod($revHandler,'updateParentRevision');
    $rev_id = 1;
    $frst = $this->entityHelper->paragraphs[1];
    $scnd = $this->entityHelper->paragraphs[2];
    $scnd->getParentEntity()->willReturn($frst->reveal());
    $scnd->updateLoadedRevisionId()->will(function()use (&$rev_id){
      $rev_id = 2;
      return $this->reveal();
    });
    $scnd->getRevisionId()->will(function () use (&$rev_id) {
      return $rev_id;
    });
    $scndObj = $scnd->reveal();
    $method->invoke($revHandler, $scndObj);
    $frst->save()->shouldHaveBeenCalled();
    $parent = $scndObj->get('parent_field_name')->getValue()[0]['value'];
    $value = $frst->reveal()->get($parent)->getValue()[0];
    self::assertArrayHasKey('target_revision_id', $value);
    self::assertSame(2, $value['target_revision_id']);
  }

  /**
   * @covers ::cleanUp
   */
  public function testCleanUp(){
    $paragraphs = $this->entityHelper->paragraphs;
    $paragraph = $paragraphs[2];
    // Mock RevisionHandler:
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(array('removeUnused'))->getMock();
    $arr = $this->isType('array');
    $revHandler->expects($this->atLeastOnce())
      ->method('removeUnused')
      ->with($arr, $arr, $this->isInstanceOf(FieldConfigInterface::class));
    // Mock Importer:
    $importer = $this->getMockBuilder(Importer::class)
      ->disableOriginalConstructor()
      ->getMock();
    $importer->expects($this->atLeastOnce())
      ->method('loadTarget')
      ->with($this->isInstanceOf(EntityInterface::class), $this->isInstanceOf(FieldConfigInterface::class))
      ->willReturn(array($paragraph->reveal(),$paragraph->reveal()));
    $this->updateProperty(RevisionHandler::class, $revHandler, 'importer', $importer);
    $this->updateProperty(RevisionHandler::class, $revHandler, 'entity', $this->node->reveal());
    $field = end($this->fieldHelper->fields)->reveal();
    $info = $this->getTargetInfo();
    $info->paragraphs = array($paragraph->reveal());
    $field->set('target_info', $info);
    // And call the method:
    $method = $this->getMethod($revHandler, 'cleanUp');
    $method->invoke($revHandler, array($field));
  }

  /**
   * @covers ::removeUnused
   */
  public function testRemoveUnused(){
    // Mock RevisionHandler:
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(array('createRevision'))->getMock();
    $revHandler->expects($this->never())
      ->method('createRevision')
      ->with($this->isInstanceOf(Paragraph::class));
    $paragraphs = $this->entityHelper->paragraphs;
    // Add additional field to test in common fields removal functionality:
    $parProph = $paragraphs[2];
    $anotherValue = array(array('value' => 'Test value'));
    $itemList = $this->prophesize(EntityReferenceRevisionsFieldItemList::class);
    $itemList->getValue()->willReturn($anotherValue);
    $parProph->get('another_field')->willReturn($itemList->reveal());
    $paragraph = $parProph->reveal();
    $used_entities = array($paragraph);
    $attached = array($paragraph, $paragraph);
    $field = end($this->fields)->reveal();
    $info = $this->getTargetInfo();
    $info->paragraphs = array($paragraph);
    $info->in_common = array(
      array('name' => 'another_field'),
    );
    $field->set('target_info', $info);
    $method = $this->getMethod(RevisionHandler::class,'removeUnused');
    $method->invoke($revHandler, $used_entities, $attached, $field);

    // Test with no in common fields:
    $info->in_common = array();
    $field->set('target_info', $info);

    // Mock RevisionHandler to expect a call to createRevision method:
    $revHandler = $this->getMockBuilder(RevisionHandler::class)
      ->disableOriginalConstructor()
      ->setMethods(array('createRevision'))->getMock();
    $revHandler->expects($this->atLeastOnce())
      ->method('createRevision')
      ->with($this->isInstanceOf(Paragraph::class));
    $method->invoke($revHandler, $used_entities, $attached, $field);
    $parent = $paragraph->getParentEntity();
    $parent_field = $paragraph->get('parent_field_name')->getValue()[0]['value'];
    $parentValue = $parent->get($parent_field)->getValue();
    self::assertTrue(!isset($parentValue['target_id']), 'The parent field value is cleaned up');
  }
}