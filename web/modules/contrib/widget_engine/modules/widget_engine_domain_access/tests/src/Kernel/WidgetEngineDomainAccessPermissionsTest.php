<?php

namespace Drupal\Tests\widget_engine_domain_access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;
use Drupal\Tests\widget_engine\Traits\WidgetTypeCreationTrait;

/**
 * @group widget_engine_domain_access
 */
class WidgetEngineDomainAccessPermissionsTest extends KernelTestBase {

  use WidgetTypeCreationTrait;
  use DomainTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'domain',
    'domain_access',
    'widget_engine',
    'widget_engine_domain_access',
  ];

  /**
   * Base widget ID.
   *
   * @var string
   */
  private $widgetType = 'test_widget';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createWidgeType(['type' => $this->widgetType]);
  }

  /**
   * Permissions generation test.
   */
  public function testPermissions() {
    $permissionsManager = \Drupal::service('user.permissions');
    $permissions = [];
    foreach ($permissionsManager->getPermissions() as $permission_name => $permission) {
      if ($permission['provider'] == 'widget_engine_domain_access') {
        $permissions[] = $permission_name;
      }
    }

    $expected_permissions[] = 'save widgets on any domain';
    $expected_permissions[] = 'save widgets on any assigned domain';
    $expected_permissions[] = 'create domain widgets';
    $expected_permissions[] = 'edit domain widgets';
    $expected_permissions[] = 'delete domain widgets';
    $expected_permissions[] = 'view unpublished domain widgets';
    $expected_permissions[] = 'create ' . $this->widgetType . ' widget on assigned domains';
    $expected_permissions[] = 'update ' . $this->widgetType . ' widget on assigned domains';
    $expected_permissions[] = 'delete ' . $this->widgetType . ' widget on assigned domains';

    $this->assertTrue(empty(array_diff($permissions, $expected_permissions)), 'Permissions generated correctly');
  }

}
