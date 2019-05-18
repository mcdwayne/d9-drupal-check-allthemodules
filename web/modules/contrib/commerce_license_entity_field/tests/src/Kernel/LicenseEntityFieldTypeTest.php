<?php

namespace Drupal\Tests\commerce_license_entity_field\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the entity field license type.
 *
 * @group commerce_license_entity_field
 */
class LicenseEntityFieldTypeTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity',
    'recurring_period',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_product',
    'commerce_license',
    'field',
    'dynamic_entity_reference',
    'commerce_license_entity_field',
    'entity_test',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * The license type plugin manager.
   */
  protected $licenseTypeManager;

  /**
   * The entity_test storage.
   */
  protected $entityTestStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');

    // Install the bundle plugins for the license entity type which this
    // module provides. This takes care of creating the fields which the bundle
    // plugins define.
    $this->container->get('entity.bundle_plugin_installer')->installBundles(
      $this->container->get('entity_type.manager')->getDefinition('commerce_license'),
      ['commerce_license_entity_field']
    );

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');
    $this->licenseTypeManager = $this->container->get('plugin.manager.commerce_license_type');
    $this->entityTestStorage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    // Create a boolean field on the test entity type.
    $this->entityTypeManager->getStorage('field_storage_config')->create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_field',
      'type' => 'boolean',
      'settings' => [],
    ])->save();
    $field = $this->entityTypeManager->getStorage('field_config')->create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'Comment settings',
    ]);
    $field->save();
  }

  /**
   * Tests a license sets the value on the target entity.
   */
  public function testLicenseGrantRevoke() {
    // Create a user who owns the license and target entity.
    $owner = $this->createUser();

    // Create a target entity.
    $target_entity = $this->entityTestStorage->create([
      // The type is set by EntityTest::preCreate().
      'name' => $this->randomString(),
      'user_id' => $owner->id(),
    ]);
    $target_entity->save();

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'entity_field',
      'state' => 'new',
      'product' => 1,
      'uid' => $owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_target_entity' => $target_entity,
      'license_target_field' => 'test_field',
      'license_target_value' => 1,
    ]);
    $license->save();

    $target_entity = $this->reloadEntity($target_entity);

    // Assert the field is not set.
    $value = $target_entity->test_field->value;
    $this->assertEmpty($value, "The target field on the target entity does not have the value set.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    $license->getTypePlugin()->grantLicense($license);

    // The field should now be set.
    $target_entity = $this->reloadEntity($target_entity);
    $value = $target_entity->test_field->value;
    $this->assertEquals(1, $value, "The target field on the target entity has the value set.");

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);

    // The field should now be unset.
    $target_entity = $this->reloadEntity($target_entity);
    $value = $target_entity->test_field->value;
    $this->assertEmpty($value, "The target field on the target entity does not have the value set.");
  }

  /**
   * Tests a license receives field values from a configured plugin.
   */
  public function testLicenseCreationFromPlugin() {
    $license_owner = $this->createUser();

    // Create a license which doesn't have any type-specific field values set.
    $license = $this->licenseStorage->create([
      'type' => 'entity_field',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Create a configured entity field license plugin.
    $plugin_configuration = [
      'entity_field_name' => 'test_field',
      'entity_field_value' => 1,
    ];
    $license_type_plugin = $this->licenseTypeManager->createInstance('entity_field', $plugin_configuration);

    // Set the license's type-specific fields from the configured plugin.
    $license->setValuesFromPlugin($license_type_plugin);

    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEquals('test_field', $license->license_target_field->value, "The target field field was set on the license.");
    $this->assertEquals(1, $license->license_target_value->value, "The target value field was set on the license.");
  }

}
