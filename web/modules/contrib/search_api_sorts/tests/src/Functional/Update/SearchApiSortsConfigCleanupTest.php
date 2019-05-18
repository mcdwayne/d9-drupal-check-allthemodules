<?php

namespace Drupal\Tests\search_api_sorts\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the Search api sorts upgrade path for update 8102.
 *
 * @group search_api_sorts
 */
class SearchApiSortsConfigCleanupTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api_sorts',
    'search_api_test_db',
    'search_api_sorts_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/test_update_8102.php.gz',
    ];
  }

  /**
   * Tests that all disabled sort configs are deleted from the active config.
   *
   * @see search_api_sorts_update_8102()
   */
  public function testUpdate8102() {
    $this->container->get('module_installer')->install([
      'search_api_test_db',
      'search_api_sorts_test_views',
    ]);
    // Assert that config files in the module install directory exist before
    // running updates.
    $this->assertNotEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')->load('views_page---search_api_sorts_test_view__page_1_type'));
    $this->assertNotEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')->load('views_page---search_api_sorts_test_view__page_1_title'));

    $this->runUpdates();

    // Assert that only enabled sort configs exist after running updates.
    $this->assertEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')->load('views_page---search_api_sorts_test_view__page_1_type'));
    $this->assertNotEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')->load('views_page---search_api_sorts_test_view__page_1_title'));
  }

}
