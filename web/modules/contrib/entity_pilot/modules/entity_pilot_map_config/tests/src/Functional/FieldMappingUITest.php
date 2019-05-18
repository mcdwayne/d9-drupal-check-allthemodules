<?php

namespace Drupal\Tests\entity_pilot_map_config\Functional;

use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot_map_config\ConfigurationDifference;
use Drupal\entity_pilot_map_config\FieldMappingInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests UI for editing mapping entities.
 *
 * @group entity_pilot
 */
class FieldMappingUITest extends BrowserTestBase {
  const FIELD_NAME = 'field_images';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'hal',
    'rest',
    'text',
    'node',
    'user',
    'system',
    'field',
    'file',
    'image',
    'entity_pilot_map_config',
    'entity_pilot_map_config_test',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer entity_pilot field mappings',
    'access administration pages',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $difference = new ConfigurationDifference([
      'node' => [
        'field_image' => 'image',
        'field_foo' => 'text',
      ],
    ], [
      'node' => [
        'foo',
        'article',
      ],
    ]);
    /** @var \Drupal\entity_pilot_map_config\MappingManagerInterface $manager */
    $manager = \Drupal::service('entity_pilot_map_config.mapping_manager');
    $flight_manifest = (new FlightManifest())
      ->setSite('http://example.com')
      ->setRemoteId(12)
      ->setCarrierId('foobar');
    $manager->createFieldMappingFromConfigurationDifference($difference, $flight_manifest);
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/structure/entity-pilot/field-mappings',
      'admin/structure/entity-pilot/field-mappings/add',
      'admin/structure/entity-pilot/field-mappings/flight_12_account_foobar',
      'admin/structure/entity-pilot/field-mappings/flight_12_account_foobar/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertResponse(403);
    }
    $this->drupalLogin($this->adminUser);
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertResponse(200);
    }
  }

  /**
   * Tests administration of field mappings.
   */
  public function testMappingAdministration() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/entity-pilot');
    $this->assertLink('Field mappings');
    $this->clickLink('Field mappings');
    $this->assertUrl('admin/structure/entity-pilot/field-mappings');
    $this->assertText('http://example.com');
    $this->clickLink('Edit');
    $this->assertFieldByName('label', 'http://example.com');
    $this->assertFieldByName('mappings[0][destination_field_name]', FieldMappingInterface::IGNORE_FIELD);
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com.au',
    ], t('Save'));
    $this->assertText('Saved the http://example.com.au Field mapping.');
    $this->drupalGet('admin/structure/entity-pilot/field-mappings/flight_12_account_foobar');
    $this->assertFieldByName('label', 'http://example.com.au');
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com',
      'mappings[0][destination_field_name]' => self::FIELD_NAME,
    ], t('Save'));
    $this->assertText('Saved the http://example.com Field mapping.');
    $this->drupalGet('admin/structure/entity-pilot/field-mappings/flight_12_account_foobar');
    $this->assertFieldByName('mappings[0][destination_field_name]', self::FIELD_NAME);
    $this->drupalGet('admin/structure/entity-pilot/field-mappings');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete http://example.com?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Field mapping http://example.com deleted');
  }

}
