<?php

namespace Drupal\Tests\entity_pilot_map_config\Functional;

use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot_map_config\BundleMappingInterface;
use Drupal\entity_pilot_map_config\ConfigurationDifference;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests UI for editing mapping entities.
 *
 * @group entity_pilot
 */
class BundleMappingUITest extends BrowserTestBase {
  const BUNDLE_NAME = 'post';

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
    'administer entity_pilot bundle mappings',
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
    $manager->createBundleMappingFromConfigurationDifference($difference, $flight_manifest);
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/structure/entity-pilot/bundle-mappings',
      'admin/structure/entity-pilot/bundle-mappings/add',
      'admin/structure/entity-pilot/bundle-mappings/flight_12_account_foobar',
      'admin/structure/entity-pilot/bundle-mappings/flight_12_account_foobar/delete',
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
   * Tests administration of bundle mappings.
   */
  public function testMappingAdministration() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/entity-pilot');
    $this->assertLink('Bundle mappings');
    $this->clickLink('Bundle mappings');
    $this->assertUrl('admin/structure/entity-pilot/bundle-mappings');
    $this->assertText('http://example.com');
    $this->clickLink('Edit');
    $this->assertFieldByName('label', 'http://example.com');
    $this->assertFieldByName('mappings[0][destination_bundle_name]', BundleMappingInterface::IGNORE_BUNDLE);
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com.au',
    ], t('Save'));
    $this->assertText('Saved the http://example.com.au Bundle mapping.');
    $this->drupalGet('admin/structure/entity-pilot/bundle-mappings/flight_12_account_foobar');
    $this->assertFieldByName('label', 'http://example.com.au');
    $this->drupalPostForm(NULL, [
      'label' => 'http://example.com',
      'mappings[0][destination_bundle_name]' => self::BUNDLE_NAME,
    ], t('Save'));
    $this->assertText('Saved the http://example.com Bundle mapping.');
    $this->drupalGet('admin/structure/entity-pilot/bundle-mappings/flight_12_account_foobar');
    $this->assertFieldByName('mappings[0][destination_bundle_name]', self::BUNDLE_NAME);
    $this->drupalGet('admin/structure/entity-pilot/bundle-mappings');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete http://example.com?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Bundle mapping http://example.com deleted');
  }

}
