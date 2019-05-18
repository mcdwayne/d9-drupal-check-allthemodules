<?php

/**
 * @file
 * Contains \Drupal\Tests\field_encrypt\Unit\FieldEncryptProcessEntitiesTest.
 */

namespace Drupal\Tests\field_encrypt\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\field_encrypt\FieldEncryptProcessEntities;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Tests for the FieldEncryptProcessEntities service.
 *
 * @ingroup field_encrypt
 *
 * @group field_encrypt
 *
 * @coversDefaultClass \Drupal\field_encrypt\FieldEncryptProcessEntities
 */
class FieldEncryptProcessEntitiesTest extends UnitTestCase {

  /**
   * A mock entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * A mock field.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * A mock query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * A mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * A mock encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryptService;

  /**
   * A mock encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * A mock EncryptionProfile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface
   */
  protected $encryptionProfile;

  /**
   * A mock EncryptedFieldValue entity manager.
   *
   * @var \Drupal\field_encrypt\EncryptedFieldValueManagerInterface
   */
  protected $encryptedFieldValueManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

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
      ->method('getTranslationLanguages')
      ->will($this->returnValue([$language]));
    $this->entity->expects($this->any())
      ->method('getTranslation')
      ->will($this->returnSelf());

    // Set up a mock field.
    $this->field = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock QueryFactory.
    $this->queryFactory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock EntityTypeManager.
    $this->entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock EncryptService.
    $this->encryptService = $this->getMockBuilder('\Drupal\encrypt\EncryptServiceInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for EncryptService.
    $this->encryptService->expects($this->any())
      ->method('encrypt')
      ->will($this->returnValue('encrypted text'));
    $this->encryptService->expects($this->any())
      ->method('decrypt')
      ->will($this->returnValue('decrypted text'));

    // Set up a mock EncryptionProfileManager.
    $this->encryptionProfileManager = $this->getMockBuilder('\Drupal\encrypt\EncryptionProfileManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->encryptionProfile = $this->getMockBuilder(EncryptionProfileInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for EncryptionProfileManager.
    $this->encryptionProfileManager->expects($this->any())
      ->method('getEncryptionProfile')
      ->will($this->returnValue($this->encryptionProfile));

    // Set up a mock EncryptedFieldValueManager.
    $this->encryptedFieldValueManager = $this->getMockBuilder('\Drupal\field_encrypt\EncryptedFieldValueManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $module_handler = $this->getMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->any())
      ->method('alter');
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);
  }

  /**
   * Test method entityHasEncryptedFields().
   *
   * @covers ::__construct
   * @covers ::entityHasEncryptedFields
   * @covers ::checkField
   *
   * @dataProvider entityHasEncryptedFieldsDataProvider
   */
  public function testEntityHasEncryptedFields($encrypted, $expected) {
    $definition = $this->getMockBuilder('\Drupal\Core\Field\BaseFieldDefinition')
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder('\Drupal\Core\Field\FieldConfigStorageBase')
      ->setMethods(['getThirdPartySetting'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for storage.
    $storage->expects($this->once())
      ->method('getThirdPartySetting')
      ->will($this->returnValue($encrypted));

    // Set up expectations for definition.
    $definition->expects($this->once())
      ->method('get')
      ->will($this->returnValue($storage));

    // Set up expectations for field.
    $this->field->expects($this->once())
      ->method('getFieldDefinition')
      ->will($this->returnValue($definition));

    // Set up expectations for entity.
    $this->entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue([$this->field]));

    $service = new FieldEncryptProcessEntities(
      $this->queryFactory,
      $this->entityManager,
      $this->encryptService,
      $this->encryptionProfileManager,
      $this->encryptedFieldValueManager
    );
    $return = $service->entityHasEncryptedFields($this->entity);
    $this->assertEquals($expected, $return);
  }

  /**
   * Data provider for testEntityHasEncryptedFields method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function entityHasEncryptedFieldsDataProvider() {
    return [
      'encrypted_fields' => [TRUE, TRUE],
      'no_encrypted_fields' => [FALSE, FALSE],
    ];
  }

  /**
   * Tests the encryptEntity / decryptEntity methods.
   *
   * @covers ::__construct
   * @covers ::encryptEntity
   * @covers ::decryptEntity
   * @covers ::processEntity
   * @covers ::processField
   * @covers ::processValue
   * @covers ::getUnencryptedPlaceholderValue
   *
   * @dataProvider encyptDecryptEntityDataProvider
   */
  public function testEncyptDecryptEntity($field_type, $property_definitions, $properties, $field_value, $expected_placeholder, $encrypted) {
    // Set up field definition.
    $definition = $this->getMockBuilder('\Drupal\Core\Field\BaseFieldDefinition')
      ->setMethods(['get', 'getType'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up field storage.
    $storage = $this->getMockBuilder('\Drupal\Core\Field\FieldConfigStorageBase')
      ->setMethods(['getThirdPartySetting', 'getPropertyDefinitions'])
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for storage.
    $storage_map = [
      ['field_encrypt', 'encrypt', FALSE, $encrypted],
      ['field_encrypt', 'encryption_profile', [], 'test_encryption_profile'],
      ['field_encrypt', 'properties', [], $properties],
    ];
    $storage->expects($this->any())
      ->method('getThirdPartySetting')
      ->will($this->returnValueMap($storage_map));

    $storage->expects($this->any())
      ->method('getPropertyDefinitions')
      ->will($this->returnValue($property_definitions));

    // Set up expectations for definition.
    $definition->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['field_name', 'test_field'],
        ['fieldStorage', $storage],
      ]);

    $definition->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($field_type));

    // Set up expectations for field.
    $this->field->expects($this->any())
      ->method('getFieldDefinition')
      ->will($this->returnValue($definition));

    if ($encrypted) {
      $this->field->expects($this->once())
        ->method('getValue')
        ->will($this->returnValue($field_value));
      $this->field->expects($this->once())
        ->method('setValue')
        ->with($expected_placeholder);
    }
    else {
      $this->field->expects($this->never())
        ->method('getValue');
      $this->field->expects($this->never())
        ->method('setValue');
    }

    // Set expectations for entity.
    $this->entity->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue([$this->field]));

    // Set up a mock for the EncryptionProfile class to mock some methods.
    $service = $this->getMockBuilder('\Drupal\field_encrypt\FieldEncryptProcessEntities')
      ->setMethods(['checkField', 'allowEncryption'])
      ->setConstructorArgs(array(
        $this->queryFactory,
        $this->entityManager,
        $this->encryptService,
        $this->encryptionProfileManager,
        $this->encryptedFieldValueManager,
      ))
      ->getMock();

    // Mock some methods on FieldEncryptProcessEntities, since they are out of
    // scope of this specific unit test.
    $service->expects($this->once())
      ->method('checkField')
      ->will($this->returnValue(TRUE));
    $service->expects($this->any())
      ->method('allowEncryption')
      ->will($this->returnValue(TRUE));

    $service->encryptEntity($this->entity);
  }

  /**
   * Data provider for testEncyptDecryptEntity method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function encyptDecryptEntityDataProvider() {
    return [
      'encrypted_string' => [
        'string',
        [
          'value' => new DataDefinition([
            'type' => 'string',
            'required' => TRUE,
            'settings' => ['case_sensitive' => FALSE],
          ]),
        ],
        ['value' => 'value'],
        [['value' => 'unencrypted text']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_string_long' => [
        'string_long',
        [
          'value' => new DataDefinition([
            'type' => 'string',
            'required' => TRUE,
            'settings' => ['case_sensitive' => FALSE],
          ]),
        ],
        ['value' => 'value'],
        [['value' => 'unencrypted text']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_text' => [
        'text',
        [
          'value' => new DataDefinition(['type' => 'string', 'required' => TRUE]),
          'format' => new DataDefinition(['type' => 'filter_format']),
          'processed' => new DataDefinition([
            'type' => 'string',
            'computed' => TRUE,
            'class' => '\Drupal\text\TextProcessed',
            'settings' => ['text source' => 'value'],
          ]),
        ],
        ['value' => 'value', 'format' => 'format'],
        [['value' => '<p>unencrypted text</p>', 'format' => 'basic_html']],
        [['value' => '[ENCRYPTED]', 'format' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_text_long' => [
        'text_long',
        [
          'value' => new DataDefinition(['type' => 'string', 'required' => TRUE]),
          'format' => new DataDefinition(['type' => 'filter_format']),
          'processed' => new DataDefinition([
            'type' => 'string',
            'computed' => TRUE,
            'class' => '\Drupal\text\TextProcessed',
            'settings' => ['text source' => 'value'],
          ]),
        ],
        ['value' => 'value', 'format' => 'format'],
        [['value' => '<p>unencrypted text</p>', 'format' => 'basic_html']],
        [['value' => '[ENCRYPTED]', 'format' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_text_with_summary' => [
        'text_with_summary',
        [
          'value' => new DataDefinition(['type' => 'string', 'required' => TRUE]),
          'format' => new DataDefinition(['type' => 'filter_format']),
          'processed' => new DataDefinition([
            'type' => 'string',
            'computed' => TRUE,
            'class' => '\Drupal\text\TextProcessed',
            'settings' => ['text source' => 'value'],
          ]),
          'summary' => new DataDefinition(['type' => 'string', 'required' => TRUE]),
          'summary_processed' => new DataDefinition([
            'type' => 'string',
            'computed' => TRUE,
            'class' => '\Drupal\text\TextProcessed',
            'settings' => ['text source' => 'summarys'],
          ]),
        ],
        ['value' => 'value', 'summary' => 'summary', 'format' => 'format'],
        [['value' => '<p>unencrypted text</p>', 'summary' => 'summary', 'format' => 'basic_html']],
        [['value' => '[ENCRYPTED]', 'summary' => '[ENCRYPTED]', 'format' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_list_string' => [
        'list_string',
        [
          'value' => new DataDefinition([
            'type' => 'string',
            'required' => TRUE,
            'constraints' => ['Length' => ['max' => 255]]
          ]),
        ],
        ['value' => 'value'],
        [['value' => 'value1']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_email' => [
        'email',
        [
          'value' => new DataDefinition(['type' => 'email', 'required' => TRUE]),
        ],
        ['value' => 'value'],
        [['value' => 'test@example.com']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_date' => [
        'datetime',
        [
          'value' => new DataDefinition([
            'type' => 'datetime_iso8601', 'required' => TRUE
          ]),
          'date' => new DataDefinition([
            'type' => 'any',
            'computed' => TRUE,
            'class' => '\Drupal\datetime\DateTimeComputed',
            'settings' => ['date source' => 'value'],
          ])
        ],
        ['value' => 'value'],
        [['value' => '1984-10-04T00:00:00']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_link' => [
        'link',
        [
          'uri' => new DataDefinition(['type' => 'uri']),
          'title' => new DataDefinition(['type' => 'string']),
          'options' => new DataDefinition(['type' => 'map']),
        ],
        ['uri' => 'uri', 'title' => 'title'],
        [[
          'title' => 'Drupal.org',
          'attributes' => [],
          'options' => [],
          'uri' => 'https://drupal.org',
        ]],
        [[
          'title' => '[ENCRYPTED]',
          'uri' => '[ENCRYPTED]',
          'options' => [],
          'attributes' => []
        ]],
        TRUE,
      ],
      'encrypted_int' => [
        'integer',
        ['value' => new DataDefinition([
          'type' => 'integer',
          'required' => TRUE]
        )],
        ['value' => 'value'],
        [['value' => '42']],
        [['value' => 0]],
        TRUE,
      ],
      'encrypted_float' => [
        'float',
        ['value' => new DataDefinition([
            'type' => 'float',
            'required' => TRUE]
        )],
        ['value' => 'value'],
        [['value' => '3.14']],
        [['value' => 0]],
        TRUE,
      ],
      'encrypted_decimal' => [
        'decimal',
        ['value' => new DataDefinition([
            'type' => 'string',
            'required' => TRUE]
        )],
        ['value' => 'value'],
        [['value' => '3.14']],
        [['value' => 0]],
        TRUE,
      ],
      'encrypted_boolean' => [
        'boolean',
        ['value' => new DataDefinition([
            'type' => 'boolean',
            'required' => TRUE]
        )],
        ['value' => 'value'],
        [['value' => 1]],
        [['value' => 0]],
        TRUE,
      ],
      'encrypted_telephone' => [
        'telephone',
        ['value' => new DataDefinition([
            'type' => 'string',
            'required' => TRUE]
        )],
        ['value' => 'value'],
        [['value' => '+1-202-555-0161']],
        [['value' => '[ENCRYPTED]']],
        TRUE,
      ],
      'encrypted_entity_reference' => [
        'entity_reference',
        [
          'target_id' => new DataDefinition([
            'type' => 'integer',
            'settings' => ['unsigned' => TRUE],
            'required' => TRUE
          ]),
          'entity' => new DataDefinition([
            'type' => 'entity_reference',
            'computed' => TRUE,
            'read-only' => FALSE,
            'constraints' => ['EntityType' => 'user'],
          ]),
        ],
        ['target_id' => 'target_id'],
        [['target_id' => 1]],
        [['target_id' => 0]],
        TRUE,
      ],
      'not_encrypted' => ['text', [], [], 'unencrypted text', NULL, FALSE],
    ];
  }

  /**
   * Tests the updateStoredField method.
   *
   * @covers ::__construct
   * @covers ::updateStoredField
   *
   * @dataProvider updateStoredFieldDataProvider
   */
  public function testUpdateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id) {
    // Set up entity storage mock.
    $entity_storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up a mock entity type.
    $entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Set up expectations for entity type.
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->will($this->returnValue(TRUE));

    // Set up expectations for entity storage.
    $entity_storage->expects($this->any())
      ->method('loadRevision')
      ->will($this->returnValue($this->entity));
    $entity_storage->expects($this->never())
      ->method('load');

    // Set up expectations for entity manager.
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));
    $this->entityManager->expects($this->once())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    // Set up expectations for entity.
    $this->entity->expects($this->once())
      ->method('get')
      ->with($field_name)
      ->will($this->returnValue($this->field));
    $this->entity->expects($this->once())
      ->method('save');

    // Set up a mock for the EncryptionProfile class to mock some methods.
    $service = $this->getMockBuilder('\Drupal\field_encrypt\FieldEncryptProcessEntities')
      ->setMethods(['checkField', 'processField', 'allowEncryption'])
      ->setConstructorArgs(array(
        $this->queryFactory,
        $this->entityManager,
        $this->encryptService,
        $this->encryptionProfileManager,
        $this->encryptedFieldValueManager,
      ))
      ->getMock();

    if (!empty($original_encryption_settings)) {
      $service->expects($this->once())
        ->method('processField');
    }
    else {
      $service->expects($this->never())
        ->method('processField');
    }

    $service->expects($this->any())
      ->method('checkField')
      ->will($this->returnValue(TRUE));
    $service->expects($this->any())
      ->method('allowEncryption')
      ->will($this->returnValue(TRUE));

    $service->updateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id);
  }

  /**
   * Data provider for testUpdateStoredField method.
   *
   * @return array
   *   An array with data for the test method.
   */
  public function updateStoredFieldDataProvider() {
    return [
      'no_decrypt' => [
        'field_test',
        'node',
        [],
        1,
      ],
      'decrypt' => [
        'field_test',
        'node',
        [
          'field_encrypt' => TRUE,
          'properties' => ['value'],
          'encryption_profile' => 'test_encryption_profile',
        ],
        1,
      ],
    ];
  }

}
