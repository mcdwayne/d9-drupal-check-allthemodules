<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta11Beta12UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the beta 11 to beta 12 update path.
 *
 * @group beta2beta
 */
class Beta11Beta12UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;
  use mysqlTableCollationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 11;

  /**
   * Tests update for core issue 2498919.
   */
  public function testUpdate2498919() {
    // Load node and term data.
    require __DIR__ . '/../../../tests/fixtures/drupal-8.node-with-term.beta11.php';

    // UID and status fields should allow null values when update starts.
    $this->assertEqual('YES', $this->mysqlColumnInformation('node_field_data', 'uid', 'Null'), 'Null values allowed in uid column before running updates');
    $this->assertEqual('YES', $this->mysqlColumnInformation('node_field_revision', 'uid', 'Null'), 'Null values allowed in uid column before running updates');
    $this->assertEqual('YES', $this->mysqlColumnInformation('node_field_revision', 'status', 'Null'), 'Null values allowed in status column before running updates');

    $this->runUpdates();

    $this->assertEqual('NO', $this->mysqlColumnInformation('node_field_data', 'uid', 'Null'), 'Null values not allowed in uid column after running updates');
    $this->assertEqual('NO', $this->mysqlColumnInformation('node_field_revision', 'uid', 'Null'), 'Null values not allowed in uid column after running updates');
    $this->assertEqual('NO', $this->mysqlColumnInformation('node_field_revision', 'status', 'Null'), 'Null values not allowed in status column after running updates');
  }

  /**
   * MySQL multibyte update (issue #1314214).
   *
   * @see head2head_1314214
   */
  public function testMysqlMultibyte() {
    // Can only run this on MySQL.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->pass('Skipping ' . __FUNCTION__ . ' since it can only be run on MySQL.');
      return;
    }

    // Import our content.
    require __DIR__ . '/../../../tests/fixtures/drupal-8.node-with-term.beta11.php';

    // Tables to check the collation.
    $tables = [
      'comment_entity_statistics',
      'file_usage',
      'history',
      'node_access',
      'users',
      'watchdog',

      // Dynamic table sampling.
      'cache_bootstrap',
      'cache_config',
      'cachetags',
      'menu_tree',

      // Entity tables.
      'node_field_revision',
      'shortcut',
      'taxonomy_term_field_data',
      'users',
    ];

    // Ensure expected starting collation.
    foreach ($tables as $table) {
      $collation = $this->getTableCollation($table);
      $expected = 'utf8_general_ci';
      $this->assertIdentical($expected, $collation, SafeMarkup::format('Table collation (%collation) matches expected (%expected) for %table table.', ['%expected' => $expected, '%collation' => $collation, '%table' => $table]));
    }

    // Ensure starting indexes are expected.
    $indexes = $this->getTableIndexes('file_managed');
    $this->assertTrue(isset($indexes['unique keys']['file_field__uri']), 'The unique index on file_managed.uri exists.');
    $this->assertFalse(isset($indexes['indexes']['file_field__uri']), 'A non-unique index on file_managed.uri does not exist.');

    $this->runUpdates();

    // Ensure collation has been updated.
    foreach ($tables as $table) {
      $collation = $this->getTableCollation($table);
      $expected = 'utf8mb4_general_ci';
      $this->assertIdentical($expected, $collation, SafeMarkup::format('Table collation (%collation) matches expected (%expected) for %table table.', ['%expected' => $expected, '%collation' => $collation, '%table' => $table]));
    }

    $this->drupalGet('node/1');
    $this->assertText('Test article');

    // Ensure unique indexes have been replaced.
    $indexes = $this->getTableIndexes('file_managed');
    $this->assertFalse(isset($indexes['unique keys']['file_field__uri']), 'The unique index on file_managed.uri was removed.');
    $this->assertTrue(isset($indexes['indexes']['file_field__uri']), 'An index was created on file_managed.uri.');
  }

  /**
   * Get column information from the database for a given column.
   *
   * @param string $table
   *   The table name.
   * @param string $column
   *   The database column name.
   * @param string $property
   *   The property to return.
   */
  protected function mysqlColumnInformation($table, $column, $property) {
    $query = Database::getConnection()->query("DESCRIBE {" . $table . "}");
    while ($row = $query->fetchAssoc()) {
      if ($row['Field'] === $column) {
        return $row[$property];
      }
    }
  }

  /**
   * Returns indexes for a given table.
   *
   * @param string $table
   *   The table to find indexes for.
   *
   * @return array
   *   The 'primary key', 'unique keys', and 'indexes' portion of the Drupal
   *   table schema.
   */
  protected function getTableIndexes($table) {
    $query = db_query("SHOW INDEX FROM {" . $table . "}");
    $definition = [];
    while ($row = $query->fetchAssoc()) {
      $index_name = $row['Key_name'];
      $column = $row['Column_name'];
      // Key the arrays by the index sequence for proper ordering (start at 0).
      $order = $row['Seq_in_index'] - 1;

      // If specified, add length to the index.
      if ($row['Sub_part']) {
        $column = [$column, $row['Sub_part']];
      }

      if ($index_name === 'PRIMARY') {
        $definition['primary key'][$order] = $column;
      }
      elseif ($row['Non_unique'] == 0) {
        $definition['unique keys'][$index_name][$order] = $column;
      }
      else {
        $definition['indexes'][$index_name][$order] = $column;
      }
    }
    return $definition;
  }

}
