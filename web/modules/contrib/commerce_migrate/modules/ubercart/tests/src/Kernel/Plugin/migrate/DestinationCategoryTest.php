<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate;

use Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate\DestinationCategoryTestBase;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class DestinationCategoryTest extends DestinationCategoryTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'action',
    'address',
    'comment',
    'commerce',
    'commerce_log',
    'commerce_migrate',
    'commerce_migrate_ubercart',
    'commerce_order',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'commerce_tax',
    'datetime',
    'entity',
    'entity_reference_revisions',
    'filter',
    'image',
    'inline_entity_form',
    'link',
    'node',
    'options',
    'path',
    'profile',
    'physical',
    'state_machine',
    'taxonomy',
    'telephone',
    'text',
    'text',
    'views',
  ];

  /**
   * Tests Ubercart 6 migrations are tagged as either Configuration or Content.
   */
  public function testUbercart6Categories() {
    $dirs = $this->moduleHandler->getModuleDirectories();
    $commerce_migrate_ubercart_directory = $dirs['commerce_migrate_ubercart'];
    $this->loadFixture("$commerce_migrate_ubercart_directory/tests/fixtures/uc6.php");
    $migrations = $this->migrationPluginManager->createInstancesByTag('Ubercart');
    $ubercart6_migrations = $this->filterMigrations($migrations, 'Drupal 6');
    $this->assertArrayHasKey('uc6_order', $ubercart6_migrations);
    $this->assertCategories($ubercart6_migrations);
  }

  /**
   * Tests Ubercart 7 migrations are tagged as either Configuration or Content.
   */
  public function testUbercart7Categories() {
    $dirs = $this->moduleHandler->getModuleDirectories();
    $commerce_migrate_ubercart_directory = $dirs['commerce_migrate_ubercart'];
    $this->loadFixture("$commerce_migrate_ubercart_directory/tests/fixtures/uc7.php");
    $migrations = $this->migrationPluginManager->createInstancesByTag('Drupal 7');
    $ubercart7_migrations = $this->filterMigrations($migrations, 'Drupal 7');
    $this->assertArrayHasKey('uc7_store', $ubercart7_migrations);
    $this->assertCategories($ubercart7_migrations);
  }

  /**
   * Filter the migrations by a single tag.
   *
   * @param array $migrations
   *   An array of migrations.
   * @param string $tag
   *   The filter tag.
   *
   * @return array
   *   The migrations with the tag.
   */
  protected function filterMigrations(array $migrations, $tag) {
    $filtered_migrations = [];
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $id => $migration) {
      $tags = $migration->getMigrationTags();
      if (in_array($tag, $tags)) {
        $filtered_migrations[$id] = $migration;
      }
    }
    return $filtered_migrations;
  }

}
