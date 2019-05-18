<?php

namespace Drupal\field_encrypt\Tests;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\key\Entity\Key;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Base test class for field_encrypt.
 */
abstract class FieldEncryptTestBase extends WebTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'field',
    'field_ui',
    'text',
    'locale',
    'content_translation',
    'key',
    'encrypt',
    'encrypt_test',
    'field_encrypt',
  ];

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;


  /**
   * A list of test keys.
   *
   * @var \Drupal\key\Entity\Key[]
   */
  protected $testKeys;

  /**
   * A list of test encryption profiles.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile[]
   */
  protected $encryptionProfiles;

  /**
   * The page node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A test node.
   *
   * @var \Drupal\node\Entity\Node $testNode
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   *
   * @TODO: Simplify setUp() by extending EncryptTestBase when https://www.drupal.org/node/2692387 lands.
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->container->get('entity.manager');

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
      'administer field encryption',
    ], NULL, TRUE);
    $this->drupalLogin($this->adminUser);

    // Create test keys for encryption.
    $key_128 = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Test Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key_128->save();
    $this->testKeys['testing_key_128'] = $key_128;

    $key_256 = Key::create([
      'id' => 'testing_key_256',
      'label' => 'Test Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '256'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbitmustbesixteenbit'],
    ]);
    $key_256->save();
    $this->testKeys['testing_key_256'] = $key_256;

    // Create test encryption profiles.
    $encryption_profile_1 = EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ]);
    $encryption_profile_1->save();
    $this->encryptionProfiles['encryption_profile_1'] = $encryption_profile_1;

    $encryption_profile_2 = EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration' => ['mode' => 'CFB'],
      'encryption_key' => $this->testKeys['testing_key_256']->id(),
    ]);
    $encryption_profile_2->save();
    $this->encryptionProfiles['encryption_profile_2'] = $encryption_profile_2;

    // Create content type to test.
    $this->nodeType = $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create test fields.
    $single_field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_test_single',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'cardinality' => 1,
    ));
    $single_field_storage->save();
    $single_field = FieldConfig::create([
      'field_storage' => $single_field_storage,
      'bundle' => 'page',
      'label' => 'Single field',
    ]);
    $single_field->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent('field_test_single')
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent('field_test_single', array(
        'type' => 'text_default',
      ))
      ->save();

    $multi_field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_test_multi',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 3,
    ));
    $multi_field_storage->save();
    $multi_field = FieldConfig::create([
      'field_storage' => $multi_field_storage,
      'bundle' => 'page',
      'label' => 'Multi field',
    ]);
    $multi_field->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent('field_test_multi')
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent('field_test_multi', array(
        'type' => 'string',
      ))
      ->save();
  }

  /**
   * Creates a test node.
   */
  protected function createTestNode() {
    $this->testNode = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_test_single' => [
        [
          'value' => "Lorem ipsum dolor sit amet.",
          'summary' => "Lorem ipsum",
          'format' => filter_default_format(),
        ],
      ],
      'field_test_multi' => [
        ['value' => "one"],
        ['value' => "two"],
        ['value' => "three"],
      ],
    ]);
    $this->testNode->enforceIsNew(TRUE);
    $this->testNode->save();
  }

  /**
   * Set up storage settings for test fields.
   */
  protected function setFieldStorageSettings($encryption = TRUE, $alternate = FALSE, $uncacheable = TRUE) {
    // Set up storage settings for first field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_single/storage');
    $this->assertFieldByName('field_encrypt[encrypt]', NULL, 'Encrypt field found.');
    $this->assertFieldByName('field_encrypt[encryption_profile]', NULL, 'Encryption profile field found.');

    $profile_id = ($alternate == TRUE) ? 'encryption_profile_2' : 'encryption_profile_1';
    $edit = [
      'field_encrypt[encrypt]' => $encryption,
      'field_encrypt[properties][value]' => 'value',
      'field_encrypt[properties][summary]' => 'summary',
      'field_encrypt[encryption_profile]' => $profile_id,
      'field_encrypt[uncacheable]' => $uncacheable,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_single/storage');

    // Set up storage settings for second field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_test_multi/storage');
    $this->assertFieldByName('field_encrypt[encrypt]', NULL, 'Encrypt field found.');
    $this->assertFieldByName('field_encrypt[encryption_profile]', NULL, 'Encryption profile field found.');

    $profile_id = ($alternate == TRUE) ? 'encryption_profile_1' : 'encryption_profile_2';
    $edit = [
      'field_encrypt[encrypt]' => $encryption,
      'field_encrypt[properties][value]' => 'value',
      'field_encrypt[encryption_profile]' => $profile_id,
      'field_encrypt[uncacheable]' => $uncacheable,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
  }

  /**
   * Set up translation settings for content translation test.
   */
  protected function setTranslationSettings() {
    // Set up extra language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')
      ->setEnabled('node', 'page', TRUE);
    drupal_static_reset();
    $this->entityManager->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
    $this->rebuildContainer();
  }

}
