<?php

namespace Drupal\Tests\permission_ui\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of Permission UI local tasks.
 *
 * @group permission_ui
 */
class PermissionUiLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->directoryList = [
      'permission_ui' => 'modules/contrib/permission_ui',
    ];
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getPermissionUiRoutes
   */
  public function testSystemAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getPermissionUiRoutes() {
    return [
      ['entity.user_permission.collection', [['entity.user_permission.collection']]],
    ];
  }

}
