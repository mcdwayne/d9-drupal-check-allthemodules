<?php

namespace Drupal\Tests\changed_fields\Unit;

use Drupal\changed_fields\EntitySubject;
use Drupal\Tests\UnitTestCase;
use ReflectionClass;

/**
 * @coversDefaultClass \Drupal\changed_fields\EntitySubject
 *
 * @group changed_fields
 */
class EntitySubjectTest extends UnitTestCase {

  /**
   * @var EntitySubject
   */
  private $entitySubject;

  /**
   * Sets a protected property on a given object via reflection
   *
   * @param $object - instance in which protected value is being modified
   * @param $property - property on instance being modified
   * @param $value - new value of the property being modified
   *
   * @return void
   */
  public function setProtectedProperty($object, $property, $value) {
    $reflection = new ReflectionClass($object);
    $reflection_property = $reflection->getProperty($property);
    $reflection_property->setAccessible(true);
    $reflection_property->setValue($object, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entitySubject = $this->getMockBuilder('\Drupal\changed_fields\EntitySubject')
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Observer must implement ObserverInterface interface.
   */
  public function testAttachMethod() {
    $observer_mock = $this->createMock('SplObserver');
    $this->entitySubject->attach($observer_mock);
  }

  /**
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Observer must implement ObserverInterface interface.
   */
  public function testDetachMethod() {
    $observer_mock = $this->createMock('SplObserver');
    $this->entitySubject->detach($observer_mock);
  }

  /**
   * Observers 1 and 2 are notified. Observer 3 is not notified.
   *
   * Entity: node:article.
   * Observer 1: listens to node:article.
   * Observer 2: listens to node:article and node:page.
   * Observer 3: listens to media:image.
   */
  public function testNotifyObservers() {
    $observer_1 = $this->getMockBuilder('Drupal\changed_fields\ObserverInterface')
      ->setMethods(['getInfo', 'update'])
      ->getMock();

    $observer_1->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        'node' => [
          'article' => [
            'title',
            'body',
          ],
        ],
      ]);

    $observer_1->expects($this->once())
      ->method('update');

    $observer_2 = $this->getMockBuilder('Drupal\changed_fields\ObserverInterface')
      ->setMethods(['getInfo', 'update'])
      ->getMock();

    $observer_2->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        'node' => [
          'article' => [
            'title',
            'body',
          ],
          'page' => [
            'title',
            'body',
          ],
        ],
        'user' => [
          'user' => [
            'name',
          ],
        ],
      ]);

    $observer_2->expects($this->once())
      ->method('update');

    $observer_3 = $this->getMockBuilder('Drupal\changed_fields\ObserverInterface')
      ->setMethods(['getInfo', 'update'])
      ->getMock();

    $observer_3->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        'media' => [
          'image' => [
            'name',
            'field_media_image',
          ],
        ],
        'comment' => [
          'comment' => [
            'subject',
            'comment_body',
          ],
        ],
      ]);

    $observer_3->expects($this->never())
      ->method('update');

    $field_definition_mock = $this->getMockBuilder('Drupal\field\Entity\FieldConfig')
      ->disableOriginalConstructor()
      ->setMethods(['getType'])
      ->getMock();

    $field_definition_mock->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $field_item_list_mock = $this->getMockBuilder('Drupal\Core\Field\FieldItemList')
      ->disableOriginalConstructor()
      ->setMethods(['getValue', 'getFieldDefinition'])
      ->getMock();

    $field_item_list_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([]);

    $field_item_list_mock->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($field_definition_mock);

    $entity_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->setMethods(['isNew', 'getEntityTypeId', 'bundle', 'get'])
      ->getMock();

    $entity_mock->expects($this->once())
      ->method('isNew')
      ->willReturn(FALSE);

    // Invoke 5 times: each entity type in each observer.
    $entity_mock->expects($this->exactly(5))
      ->method('getEntityTypeId')
      ->willReturn('node');

    // Invoke 3 times: each node bundle in each observer.
    $entity_mock->expects($this->exactly(3))
      ->method('bundle')
      ->willReturn('article');

    $entity_mock->expects($this->any())
      ->method('get')
      ->willReturn($field_item_list_mock);

    $original_entity_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    $original_entity_mock->expects($this->any())
      ->method('get')
      ->willReturn($field_item_list_mock);

    $field_comparator_plugin_mock = $this->getMockBuilder('Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator')
      ->disableOriginalConstructor()
      ->setMethods(['compareFieldValues'])
      ->getMock();

    $field_comparator_plugin_mock->expects($this->any())
      ->method('compareFieldValues')
      ->willReturn([]);

    $this->setProtectedProperty($entity_mock, 'fieldDefinitions', []);
    $this->setProtectedProperty($this->entitySubject, 'entity', $entity_mock);
    $this->setProtectedProperty($this->entitySubject, 'fieldComparatorPlugin', $field_comparator_plugin_mock);
    $entity_mock->original = $original_entity_mock;

    $this->entitySubject->attach($observer_1);
    $this->entitySubject->attach($observer_2);
    $this->entitySubject->attach($observer_3);
    $this->entitySubject->notify();

    $this->assertTrue($entity_mock === $this->entitySubject->getEntity());
    $this->assertEquals([
      'title' => [],
      'body' => [],
    ], $this->entitySubject->getChangedFields());
  }

  /**
   * Observer is not notified because node:article is new.
   *
   * Entity: node:article.
   */
  public function testNotifyNewEntity() {
    $observer = $this->getMockBuilder('Drupal\changed_fields\ObserverInterface')
      ->setMethods(['getInfo', 'update'])
      ->getMock();

    $observer->expects($this->never())
      ->method('getInfo');

    $observer->expects($this->never())
      ->method('update');

    $entity_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->setMethods(['isNew', 'getEntityTypeId', 'bundle'])
      ->getMock();

    $entity_mock->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);

    $entity_mock->expects($this->never())
      ->method('getEntityTypeId')
      ->willReturn('node');

    $entity_mock->expects($this->never())
      ->method('bundle')
      ->willReturn('article');

    $this->setProtectedProperty($this->entitySubject, 'entity', $entity_mock);

    $this->entitySubject->attach($observer);
    $this->entitySubject->notify();

    $this->assertTrue($entity_mock === $this->entitySubject->getEntity());
    $this->assertEquals(NULL, $this->entitySubject->getChangedFields());
  }

}
