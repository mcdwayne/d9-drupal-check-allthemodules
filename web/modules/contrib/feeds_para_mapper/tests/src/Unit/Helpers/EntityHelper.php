<?php


namespace Drupal\Tests\feeds_para_mapper\Unit\Helpers;


use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class EntityHelper
{
  /**
   * @var ObjectProphecy
   */
  public $node;

  /**
   * @var ObjectProphecy[]
   */
  public $paragraphs;

  /**
   * @var ObjectProphecy
   */
  protected $prophet;

  /**
   * @var FieldHelper
   */
  protected $fieldHelper;

  /**
   * @var array
   */
  public $values;

  /**
   * @var array
   */
  public $host_fields_values;

  public function __construct(FieldHelper $fieldHelper){
    $this->prophet = new Prophet();
    $this->node = $this->getEntity('node', $fieldHelper->node_bundle, 100);
    $this->paragraphs = array();
    $this->host_fields_values = array();
    $last = $this->node;
    foreach ($fieldHelper->fieldsConfig as $config) {
      $st = $config->settings['handler_settings'];
      if(isset($st['target_bundles'])){
        $this->values[$config->name] = array();
        foreach ($st['target_bundles'] as $target_bundle) {
          foreach ($config->paragraph_ids as $paragraph_id) {
            if(isset($config->host_field)){
              $this->host_fields_values[$config->name] = array(
                array('value' => $config->host_field)
              );
            }
            $this->paragraphs[$paragraph_id] = $this->getEntity('paragraph', $target_bundle, $paragraph_id, $config->host_field, $last->reveal());
            $this->values[$config->name][] = array(
              'target_id' => $paragraph_id
            );
            $last = $this->paragraphs[$paragraph_id];
          }
        }
      }
    }
    $this->fieldHelper = $fieldHelper;
  }

  /**
   * Creates entity object
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param int $id
   *   The entity id.
   * @param string $host_field
   *   The host field.
   * @param mixed $host
   *   The host entity.
   *
   * @return ObjectProphecy
   *   A mocked entity object.
   */
  private function getEntity($type, $bundle, $id, $host_field = null, $host = null){
    $class = Node::class;
    if($type === 'paragraph'){
      $class = Paragraph::class;
    }
    $entity = $this->prophet->prophesize($class);
    $entity->isNew()->willReturn(true);
    $that = $this;
    $entity->hasField(Argument::type('string'))->will(function($args) use ($that, $type, $bundle){
      if($type === 'node'){
        $field = $that->fieldHelper->fields[0]->reveal()->getName();
        if($field === $args[0]){
          return true;
        }
        return false;
      }
      else {
        $fields = $that->fieldHelper->getBundleFields($bundle);
        foreach ($fields as $field) {
          if($field->reveal()->getName() === $args[0]){
            return true;
          }
        }
        return false;
      }
    });
    $entity->getEntityTypeId()->willReturn($type);
    $entity->bundle()->willReturn($bundle);
    $entity->id()->willReturn($id);
    $that = $this;
    if(isset($host_field)){
      $entity->getParentEntity()->willReturn($host);
    }
    $entity->get(Argument::type('string'))->will(function($args) use ($entity, $host_field, $bundle, $that){
      if($args[0] === 'parent_field_name' ){
        return $that->getFieldItemListMock($host_field,'text');
      }
      $fields = $that->fieldHelper->getBundleFields($bundle);
      $found = array_filter($fields, function ($field) use ($args){
        $name = $field->reveal()->getName();
        return  $name === $args[0];
      })[0];
      return $that->getFieldItemListMock($args[0],'reference', $found->reveal());
    });
    $entity->getType()->willReturn($bundle);
    $entity->getFieldDefinitions()->will(function ($args) use ($that, $type, $bundle){
      return $that->fieldHelper->getFieldDefinitions($type, $bundle);
    });
    $entity->save()->willReturn(TRUE);
    return $entity;
  }

  /**
   *
   * @param string $field
   * @param string $type
   * @param mixed $instance
   *
   * @return EntityReferenceRevisionsFieldItemList
   */
  public function getFieldItemListMock($field, $type = "reference", $instance = null){
    $class  = EntityReferenceRevisionsFieldItemList::class;
    $values = &$this->values;
    if($type !== 'reference'){
      $class  = FieldItemList::class;
      $values = &$this->host_fields_values;
    }
    $fieldItem = $this->prophet->prophesize($class);
    $fieldItem->getValue()->will(function($args) use ($field, $values){
      $result = array();
      if(isset($values[$field])) {
        $result = $values[$field];
      }
      return $result;
    });
    $fieldItem->appendItem(Argument::any())->will(function($args) use ($field, &$values){
      $v = array();
      if(isset($values[$field])){
        $v = $values[$field];
      }
      $v[] = array('entity' => $args[0]);
      $values[$field] = $v;
      return $this->reveal();
    });
    $fieldItem->set(Argument::type('int'), Argument::any())->will(function($args) use ($field, &$values){
      $v = array();
      if(isset($values[$field])){
        $v = $values[$field];
      }
      $v[$args[0]] = $args[1];
      $values[$field] = $v;
      return $this->reveal();
    });
    $fieldItem->removeItem(Argument::type('int'))->will(function($args) use ($field, &$values){
      $v = array();
      if(isset($values[$field])){
        $v = $values[$field];
      }
      unset($v[$args[0]]);
      $values[$field] = $v;
      return $this->reveal();
    });
    $fieldItem->getFieldDefinition()->willReturn($instance);
    return $fieldItem->reveal();
  }

  /**
   * Attach value to a field
   * @param string $field
   *   The field.
   * @param mixed $value
   *   The value.
   */
  public function setValue($field, $value){
    $this->values[$field] = $value;
  }
  /** Creates entity manager instance.
   *
   * @return ObjectProphecy
   */
  public function getEntityTypeManagerMock(){
    $manager = $this->prophet->prophesize('Drupal\Core\Entity\EntityTypeManagerInterface');
    $storage = $this->getStorageMock()->reveal();
    try {
      $manager->getStorage(Argument::type('string'))->willReturn($storage);
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    return $manager;
  }

  /**
   *
   * @return ObjectProphecy
   *   A storage instance.
   */
  protected function getStorageMock(){
    $storage = $this->prophet->prophesize(EntityStorageInterface::class);
    $that = $this;
    $storage->create(Argument::type('array'))->will(function($args) use ($that){
      $bundle = $args[0]['type'];
      $id = random_int(10,10);
      return $that->getEntity('paragraph', $bundle, $id)->reveal();
    });
    $storage->load(Argument::any())->will(function($args) use ($that){
      $id = $args[0];
      return $that->paragraphs[$id];
    });
    return $storage;
  }
}