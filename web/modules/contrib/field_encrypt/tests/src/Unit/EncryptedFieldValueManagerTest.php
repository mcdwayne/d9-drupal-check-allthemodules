<?php

/**
 * @file
 * Contains \Drupal\Tests\field_encrypt\Unit\EncryptedFieldValueManagerTest.
 */

namespace Drupal\Tests\field_encrypt\Unit;

use Drupal\field_encrypt\EncryptedFieldValueManager;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Tests for the EncryptedFieldValueManager service.
 *
 * @ingroup field_encrypt
 *
 * @group field_encrypt
 *
 * @coversDefaultClass \Drupal\field_encrypt\EncryptedFieldValueManager
 */
class EncryptedFieldValueManagerTest extends UnitTestCase {

  /**
   * A mock entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * A mock entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * A mock entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * A mock EncryptedFieldValue entity.
   *
   * @var \Drupal\field_encrypt\Entity\EncryptedFieldValueInterface
   */
  protected $encryptedFieldValue;

  /**
   * A mock EntityStorage instance.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();

    // Set up a mock entity type manager service.
    $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock EntityStorage.
    $this->storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for the entity type manager.
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->storage));

    // Set up a mock entity query service.
    $this->entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock entity.
    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up language object.
    $language = $this->getMockBuilder('\Drupal\Core\Language\LanguageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for language.
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));

    // Set up expectations for entity.
    $this->entity->expects($this->any())
      ->method('language')
      ->will($this->returnValue($language));
    $this->entity->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));
    $this->entity->expects($this->any())
      ->method('getId')
      ->will($this->returnValue(1));
    $this->entity->expects($this->any())
      ->method('getRevisionId')
      ->will($this->returnValue(2));

    // Set up EncryptedFieldValue.
    $this->encryptedFieldValue = $this->getMockBuilder('\Drupal\field_encrypt\Entity\EncryptedFieldValueInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for EncryptedFieldValue.
    $this->encryptedFieldValue->expects($this->any())
      ->method('hasTranslation')
      ->will($this->returnValue(TRUE));
    $this->encryptedFieldValue->expects($this->any())
      ->method('getTranslation')
      ->will($this->returnSelf());
  }

  /**
   * Test the saveEncryptedFieldValue method.
   *
   * @covers ::__construct
   * @covers ::saveEncryptedFieldValues
   *
   * @dataProvider saveEncryptedFieldValueDataProvider
   */
  public function testSaveEncryptedFieldValue($existing) {
    // Set up a mock for the EncryptedFieldValueManager class to mock
    // some methods.
    /** @var \Drupal\field_encrypt\EncryptedFieldValueManager $service */
    $service = $this->getMockBuilder('\Drupal\field_encrypt\EncryptedFieldValueManager')
      ->setMethods(['getExistingEntity', 'getEntityRevisionId'])
      ->setConstructorArgs(array(
        $this->entityManager,
        $this->entityQuery,
      ))
      ->getMock();

    // Set up expectations depending on whether an existing entity exists.
    if ($existing) {
      $this->encryptedFieldValue->expects($this->once())
        ->method('setEncryptedValue');

      $this->encryptedFieldValue->expects($this->once())
        ->method('save');

      $service->expects($this->once())
        ->method('getExistingEntity')
        ->will($this->returnValue($this->encryptedFieldValue));
    }
    else {
      $service->expects($this->once())
        ->method('getExistingEntity')
        ->will($this->returnValue(FALSE));

      $this->encryptedFieldValue->expects($this->never())
        ->method('setEncryptedValue');
    }

    $service->createEncryptedFieldValue($this->entity, 'field_test', 0, 'value', 'encrypted text');
    $service->saveEncryptedFieldValues($this->entity, 'field_test', 0, 'value', 'encrypted text');
  }

  /**
   * Data provider for testSaveEncryptedFieldValue method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function saveEncryptedFieldValueDataProvider() {
    return [
      'existing' => [TRUE],
      'not_existing' => [FALSE],
    ];
  }

  /**
   * Test the getEncryptedFieldValue method.
   *
   * @covers ::__construct
   * @covers ::getEncryptedFieldValue
   *
   * @dataProvider getEncryptedFieldValueDataProvider
   */
  public function testGetEncryptedFieldValue($existing, $expected_value) {
    // Set up a mock for the EncryptedFieldValueManager class to mock some
    // methods.
    $service = $this->getMockBuilder('\Drupal\field_encrypt\EncryptedFieldValueManager')
      ->setMethods(['getExistingEntity'])
      ->setConstructorArgs(array(
        $this->entityManager,
        $this->entityQuery,
      ))
      ->getMock();

    // Set up expectations depending on whether an existing entity exists.
    if ($existing) {
      $this->encryptedFieldValue->expects($this->once())
        ->method('getEncryptedValue')
        ->will($this->returnValue("encrypted text"));

      $service->expects($this->once())
        ->method('getExistingEntity')
        ->will($this->returnValue($this->encryptedFieldValue));
    }
    else {
      $service->expects($this->once())
        ->method('getExistingEntity')
        ->will($this->returnValue(FALSE));

      $service->expects($this->never())
        ->method('getEncryptedValue');
    }

    $value = $service->getEncryptedFieldValue($this->entity, 'field_test', 0, 'value');
    $this->assertEquals($expected_value, $value);
  }

  /**
   * Data provider for testGetEncryptedFieldValue method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function getEncryptedFieldValueDataProvider() {
    return [
      'existing' => [
        TRUE,
        "encrypted text",
      ],
      'not_existing' => [
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Test the deleteEntityEncryptedFieldValues method.
   *
   * @covers ::__construct
   * @covers ::deleteEntityEncryptedFieldValues
   */
  public function testDeleteEntityEncryptedFieldValues() {
    // Set up expectations for storage.
    $this->storage->expects($this->once())
      ->method('loadByProperties')
      ->will($this->returnValue([$this->encryptedFieldValue]));

    $this->storage->expects($this->once())
      ->method('delete');

    $service = new EncryptedFieldValueManager(
      $this->entityManager,
      $this->entityQuery
    );

    $service->deleteEntityEncryptedFieldValues($this->entity);
  }

  /**
   * Test the deleteEntityEncryptedFieldValuesForField method.
   *
   * @covers ::__construct
   * @covers ::deleteEntityEncryptedFieldValuesForField
   * @covers ::getEntityRevisionId
   */
  public function testDeleteEntityEncryptedFieldValuesForField() {
    // Set up expectations for storage.
    $this->storage->expects($this->once())
      ->method('loadByProperties')
      ->will($this->returnValue([$this->encryptedFieldValue]));

    $this->storage->expects($this->once())
      ->method('delete');

    // Set up entity type object.
    $entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for entity type object.
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->will($this->returnValue(TRUE));

    $this->entity->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));

    $this->entity->expects($this->once())
      ->method('getRevisionId')
      ->will($this->returnValue(1));

    $this->entity->expects($this->never())
      ->method('id')
      ->will($this->returnValue(1));

    $service = new EncryptedFieldValueManager(
      $this->entityManager,
      $this->entityQuery
    );

    $service->deleteEntityEncryptedFieldValuesForField($this->entity, 'field_test');
  }

  /**
   * Test the deleteEncryptedFieldValuesForField method.
   *
   * @covers ::__construct
   * @covers ::deleteEncryptedFieldValuesForField
   */
  public function testDeleteEncryptedFieldValuesForField() {
    // Set up expectations for storage.
    $this->storage->expects($this->once())
      ->method('loadByProperties')
      ->will($this->returnValue([$this->encryptedFieldValue]));

    $this->storage->expects($this->once())
      ->method('delete');

    $service = new EncryptedFieldValueManager(
      $this->entityManager,
      $this->entityQuery
    );

    $service->deleteEncryptedFieldValuesForField('node', 'field_test');
  }

}
