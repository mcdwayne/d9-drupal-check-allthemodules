<?php

/**
 * @file
 * Tests for optimizedb module.
 */

namespace Drupal\optimizedb\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Testing the performance of operations on tables.
 *
 * @link admin/config/development/optimizedb/list_tables @endlink
 *
 * @group optimizedb
 */
class OptimizedbListTablesOperationExecuteTest extends WebTestBase {

  /**
   * Disabled config schema checking temporarily until all errors are resolved.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = ['optimizedb'];

  /**
   * A user with permission the settings module.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer optimizedb settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Performing operations on tables.
   */
  public function testListTablesOperationExecute() {
    $this->drupalPostForm('admin/config/development/optimizedb/list_tables', [], t('Check tables'));
    $this->assertText(t('To execute, you must select at least one table from the list.'));

    // Output all database tables.
    $tables = _optimizedb_tables_list();
    $table_name = key($tables);

    $edit = [];
    // Selected first table in list.
    $edit['tables[' . $table_name . ']'] = $table_name;

    $this->drupalPostForm('admin/config/development/optimizedb/list_tables', $edit, t('Check tables'));
    $this->assertText(t('The operation completed successfully.'));
  }

}
