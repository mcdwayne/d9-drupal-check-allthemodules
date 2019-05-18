<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta10UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\views\Entity\View;

/**
 * Tests the beta 10 update path.
 *
 * @group beta2beta
 */
class Beta10UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;
  use mysqlTableCollationTrait;

  /**
   * Turn off strict config schema checking.
   *
   * This has to be turned off since there are multiple update hooks that update
   * views. Since only the final view save will be compliant with the current
   * schema, an exception would be thrown on the first view to be saved if this
   * were left on.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 10;


  /**
   * Tests update for issue #2482295.
   */
  public function testUpdate2482295() {
    $this->assertFalse(\Drupal::keyValue('entity.definitions.bundle_field_map')->get('node'), 'No field map exists prior to beta2beta_update_81101');
    $this->runUpdates();
    $expected = [
      'body' => [
        'type' => 'text_with_summary',
        'bundles' => [
          'article' => 'article',
          'page' => 'page',
        ],
      ],
      'comment' => [
        'type' => 'comment',
        'bundles' => [
          'article' => 'article',
        ],
      ],
      'field_image' => [
        'type' => 'image',
        'bundles' => [
          'article' => 'article',
        ],
      ],
      'field_tags' => [
        'type' => 'entity_reference',
        'bundles' => [
          'article' => 'article',
          ],
      ],
    ];
    $this->assertIdentical($expected, \Drupal::keyValue('entity.definitions.bundle_field_map')->get('node'));
  }

  /**
   * Tests update for issue #2322949.
   */
  public function testUpdate2322949() {
    $this->runUpdates();
    /** @var \Drupal\views\Entity\View $view */
    $view = \Drupal::entityManager()->getStorage('view')->load('content_recent');
    $display = $view->getDisplay('default');
    $this->assertIdentical($display['display_options']['fields']['edit_node']['plugin_id'], 'entity_link_edit');
    $this->assertIdentical($display['display_options']['fields']['delete_node']['plugin_id'], 'entity_link_delete');
  }

  /**
   * Tests update for issue #1923406.
   */
  public function testUpdate1923406() {
    // Can only run this on MySQL.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->pass('Skipping ' . __FUNCTION__ . ' since it can only be run on MySQL.');
      return;
    }

    // Verify that the old schema is in place for several tables.
    $tables = [
      'cache_bootstrap' => ['checksum'],
      'cache_discovery' => ['checksum'],
      'cachetags' => ['tag'],
      'menu_tree' => ['menu_name'],
      'node' => ['type', 'uuid'],
      'node__field_tags' => ['langcode'],
      'queue' => ['name'],
      'search_index' => ['langcode', 'type'],
      'taxonomy_term_field_data' => ['description__format'],
      'url_alias' => ['langcode'],
      'users' => ['uuid'],
      'user__roles' => ['roles_target_id', 'bundle'],
      'watchdog' => ['hostname'],
    ];
    foreach ($tables as $table => $fields) {
      foreach ($fields as $field) {
        $expected = 'utf8_general_ci';
        $collation = $this->getColumnCollation($table, $field);
        $this->assertIdentical($expected, $collation, SafeMarkup::format('Expected field schema (found %collation, expected: %expected) for @table.@field', ['@table' => $table, '@field' => $field, '%collation' => $collation, '%expected' => $expected]));
      }
    }

    // Verify the expected automated entity definition updates.
    $change_summary = \Drupal::service('entity.definition_update_manager')->getChangeSummary();
    $this->assertPendingUpdates($change_summary, 4, 'file');
    $this->assertPendingUpdates($change_summary, 2, 'node');
    $this->assertPendingUpdates($change_summary, 1, 'comment');
    $this->assertPendingUpdates($change_summary, 1, 'menu_link_content');
    $this->assertPendingUpdates($change_summary, 1, 'taxonomy_term');

    $this->runUpdates();

    foreach ($tables as $table => $fields) {
      foreach ($fields as $field) {
        $expected = 'ascii_general_ci';
        $collation = $this->getColumnCollation($table, $field);
        $this->assertIdentical($expected, $collation, SafeMarkup::format('Updated field schema (found: %collation, expected: %expected) for @table.@field', ['@table' => $table, '@field' => $field, '%collation' => $collation, '%expected' => $expected]));
      }
    }

    // The varchar_ascii-related updates should be gone.
    $change_summary = \Drupal::service('entity.definition_update_manager')->getChangeSummary();
    $this->assertPendingUpdates($change_summary, 0, 'file');
    $this->assertPendingUpdates($change_summary, 0, 'node');
    $this->assertPendingUpdates($change_summary, 0, 'comment');
    $this->assertPendingUpdates($change_summary, 0, 'menu_link_content');
    $this->assertPendingUpdates($change_summary, 0, 'taxonomy_term');

    $change_summary = \Drupal::service('entity.definition_update_manager')->getChangeSummary();
    $this->assertTrue(empty($change_summary), 'No more pending updates found');

    // Check a few stored entity field schemas.
    $taxonomy = \Drupal::entityManager()->getLastInstalledFieldStorageDefinitions('taxonomy_term')['description'];
    $schema = $taxonomy->getSchema();
    $this->assertEqual('varchar_ascii', $schema['columns']['format']['type']);
    $this->assertEqual('text', $schema['columns']['value']['type']);
  }

  /**
   * Test that the block_content view is properly created.
   */
  public function testUpdate81104() {
    // View should not exist prior to updates.
    $this->assertFalse(View::load('block_content'), 'The block_content view does not already exist.');
    $this->runUpdates();
    $this->assertTrue(View::load('block_content'), 'The block_content view has been created.');

    // Browse to the listing page.
    $account = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertRaw('view-id-block_content');
    $this->assertResponse(200);
  }

  /**
   * Helper function to assert pending updates.
   */
  protected function assertPendingUpdates(array $change_list, $expected, $entity_type) {
    if ($expected === 0) {
      $this->assertFalse(isset($change_list[$entity_type]), sprintf('There are no pending entity updates to the %s entity type', $entity_type));
    }
    else {
      $this->assertIdentical(count($change_list[$entity_type]), $expected, sprintf('There are %s pending entity updates to the %s entity type', $expected, $entity_type));
    }
  }
}
