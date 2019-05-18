<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Update2509300Test.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\block\Entity\Block;
use Drupal\Core\Database\Database;

/**
 * Test that block visibility settings are updated.
 *
 * @group beta2beta
 */
class Update2509300Test extends Beta2BetaUpdateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 11;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/drupal-8.path-slash-2509300.php',
    ];
    parent::setUp();
  }

  /**
   * Tests site configuration without configured 403/404 pages.
   */
  public function testSiteSettingsWithNoConfigured403404Pages() {
    $data = [
      'uuid' => '020698b8-4200-41f0-9247-71421303d008',
      'name' => 'Site-Install',
      'mail' => 'admin@example.com',
      'slogan' => '',
      'page' => [
        403 => '',
        404 => '',
        'front' => 'custom-front-page',
      ],
      'admin_compact_mode' => FALSE,
      'weight_select_max' => 100,
      'langcode' => 'en',
      'default_langcode' => 'en',
    ];
    Database::getConnection()
      ->update('config')
      ->fields([
        'data' => serialize($data),
      ])
      ->condition('name', 'system.site')
      ->condition('collection', '')
      ->execute();

    // Clear caches that have already been populated with the previous db values.
    \Drupal::cache('config')->deleteAll();
    \Drupal::configFactory()->clearStaticCache();

    $this->runUpdates();

    $this->assertEqual('/custom-front-page', $this->config('system.site')->get('page.front'));
    $this->assertEqual('', $this->config('system.site')->get('page.403'));
    $this->assertEqual('', $this->config('system.site')->get('page.404'));
  }

  /**
   * Tests that block visibility paths are updated.
   */
  public function testBlockVisibilityUpdate() {
    $this->runUpdates();

    $block = Block::load('bartik_breadcrumbs');
    $this->assertIdentical("/user/1\n<front>", $block->get('visibility')['request_path']['pages']);

    // Browse to user/1 and ensure breadcrumbs do not display.
    $account = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($account);
    $this->drupalGet('/user/1');
    $this->assertNoRaw('class="breadcrumb"');
  }

  /**
   * Tests that url aliases are updated.
   */
  public function testUrlAliasUpdate() {
    $this->runUpdates();

    /** @var \Drupal\Core\Path\AliasStorageInterface $alias_storage */
    $alias_storage = \Drupal::service('path.alias_storage');
    $this->assertFalse($alias_storage->load(['source' => 'source1']));
    $this->assertFalse($alias_storage->load(['source' => 'source2']));
    $this->assertFalse($alias_storage->load(['alias' => 'destination1']));
    $this->assertFalse($alias_storage->load(['alias' => 'destination2']));

    $alias = $alias_storage->load(['source' => '/source1']);
    $this->assertEqual('/destination1', $alias['alias']);

    $alias = $alias_storage->load(['source' => '/source2']);
    $this->assertEqual('/destination2', $alias['alias']);

    $alias = $alias_storage->load(['alias' => '/destination1']);
    $this->assertEqual('/source1', $alias['source']);

    $alias = $alias_storage->load(['alias' => '/destination2']);
    $this->assertEqual('/source2', $alias['source']);
  }

  /**
   * Tests that custom site.settings 403/404 pages are updated.
   */
  public function testSiteSettingsUpdate() {
    $this->runUpdates();

    // Custom site settings.
    $this->assertEqual('/custom-front-page', $this->config('system.site')->get('page.front'));
    $this->assertEqual('/custom-403-page', $this->config('system.site')->get('page.403'));
    $this->assertEqual('/custom-404-page', $this->config('system.site')->get('page.404'));
  }

}
