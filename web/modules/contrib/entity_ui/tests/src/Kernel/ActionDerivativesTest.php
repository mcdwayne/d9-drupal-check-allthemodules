<?php

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests tab content plugin are derived from actions.
 *
 * @group entity_ui
 */
class ActionDerivativesTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'user',
    'action',
    // Needed for base fields on entities.
    'text',
    'node',
    'field',
    'entity_ui',
  ];

  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
  }

  /**
   * Tests derivative plugins are defined for node configurable action plugins.
   */
  public function testConfigurableActionDerivatives() {
    $entity_tab_content_manager = $this->container->get('plugin.manager.entity_ui_tab_content');

    $definitions = $entity_tab_content_manager->getDefinitions();

    $this->assertArrayHasKey('actions_configurable:node_assign_owner_action', $definitions, "A content plugin derived from a Node configurable action plugin was found.");

    $this->assertArrayNotHasKey('actions_configurable:action_send_email_action', $definitions, "Content plugins are not derived for action plugins whose type is not an entity type ID.");
  }

}
