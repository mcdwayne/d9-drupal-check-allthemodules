<?php

namespace Drupal\Tests\monster_menus\Unit\Menu\LocalTask;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests monster_menus local tasks.
 *
 * @group monster_menus
 */
class MMLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp() {
    $this->directoryList = array('monster_menus' => 'modules/contrib/monster_menus');
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider providerTestMMLocalTasks
   */
  public function testMMLocalTasks($route, $expected_results) {
    $this->assertLocalTasks($route, $expected_results);
  }

  /**
   * Data provider for ::testMMLocalTasks.
   */
  public function providerTestMMLocalTasks () {
    return [
      ['monster_menus.handle_page_settings', [
        [
          'monster_menus.contents',
          'monster_menus.handle_page_settings',
        ],
        [
          'monster_menus.handle_page_settings_edit',
          'monster_menus.page_settings_empty',
          'monster_menus.page_settings_copymove',
          'monster_menus.page_settings_restore',
          'entity.mm_tree.delete_form',
          'entity.mm_tree.add_form',
          'monster_menus.mm_ui_menu_reorder',
          'entity.mm_tree.version_history',
          'monster_menus.page_settings_search',
        ],
      ]],
      ['monster_menus.mm_admin_list_sites', [[
        'monster_menus.mm_admin_list_sites',
        'monster_menus.admin_edit_site',
      ]]],
      ['entity.mm_tree.canonical', [
        [
          'monster_menus.contents',
          'monster_menus.handle_page_settings',
        ],
        [
          'entity.mm_tree.canonical',
          'monster_menus.add_node',
          'monster_menus.reorder_nodes',
        ],
      ]],
      ['monster_menus.mm_admin_find_user', [[
        'monster_menus.mm_admin_find_user',
      ]]],
    ];
  }

}