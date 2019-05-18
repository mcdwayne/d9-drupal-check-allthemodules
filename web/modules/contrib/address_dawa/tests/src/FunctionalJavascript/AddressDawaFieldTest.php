<?php

namespace Drupal\Tests\address_dawa\Kernel\Formatter;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test address_dawa field functionality.
 *
 * @group address_dawa
 */
class AddressDawaFieldTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'language',
    'text',
    'entity_test',
    'user',
    'address_dawa',
  ];

  /**
   * Entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Address type field setting name.
   */
  const ADDRESS_TYPE_FIELD_SETTING_NAME = 'address_type';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('da')->save();

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'address_dawa',
    ]);
    $field_storage->save();

    $this->fieldConfig = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $this->fieldConfig->save();
    $this->display = entity_get_display($this->entityType, $this->bundle, 'default');
    $this->display->setComponent($this->fieldName, [
      'type' => 'address_dawa',
      'settings' => [],
    ]);
    $this->display->save();

    entity_get_form_display($this->fieldConfig->getTargetEntityTypeId(), $this->fieldConfig->getTargetBundle(), 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'address_dawa',
      ))
      ->save();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer entity_test content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests address of type "address".
   */
  public function testAddress1() {
    $this->doAddressTest([
      'id' => '0a3f50a2-33b3-32b8-e044-0003ba298018',
      'value' => 'Falstersvej 10, 3. th, 2000 Frederiksberg',
      'type' => 'adresse',
    ]);
  }

  /**
   * Tests address of type "adgangsadresse".
   */
  public function testAddress2() {
    $this->doAddressTest([
      'id' => '0a3f507a-3e85-32b8-e044-0003ba298018',
      'value' => 'Artillerivej 104, 2300 København S',
      'type' => 'adgangsadresse',
    ]);
  }

  /**
   * Do DAWA address testing.
   *
   * @param array $address
   *   Address data contains id, value and type.
   */
  protected function doAddressTest(array $address) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $entity = EntityTest::create([]);
    $this->fieldConfig
      ->setSetting(self::ADDRESS_TYPE_FIELD_SETTING_NAME, $address['type'])
      ->save();
    $entity->{$this->fieldName} = [
      'type' => '',
      'id' => '',
      'value' => '',
    ];
    $entity->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->submitForm([$this->fieldName . '[0][address]' => $address['value']], t('Save'));
    \Drupal::service('entity_type.manager')->getStorage($this->entityType)->resetCache([$entity->id()]);
    $entity = EntityTest::load($entity->id());
    // Check if we get the correct address via the UUID.
    $this->assertEquals($address['id'], $entity->{$this->fieldName}->id, 'Address ID is not correct.');
    // Also check if address type is correct.
    $this->assertEquals($address['type'], $entity->{$this->fieldName}->type, 'Address type is not correct.');

  }

}
