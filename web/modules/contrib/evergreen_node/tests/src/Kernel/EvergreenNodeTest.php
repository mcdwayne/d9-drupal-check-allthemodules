<?php

namespace Drupal\Tests\evergreen_node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the new entity API for evergreen content.
 *
 * @group evergreen_node
 * @SuppressWarnings(StaticAccess)
 */
class EvergreenNodeTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'datetime',
    'user',
    'node',
    'views',
    'evergreen',
    'evergreen_node',
  ];

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $install_schemas = ['user', 'node', 'evergreen_content'];
    foreach ($install_schemas as $schema) {
      $this->installEntitySchema($schema);
    }

    $this->service = \Drupal::service('evergreen');
    $this->plugins = \Drupal::service('plugin.manager.evergreen');
    $this->plugins->useCaches(FALSE);
    $this->plugin = $this->plugins->createInstance('node');
  }

  /**
   * Test that the node provider is available for Evergreen.
   */
  public function testNodeProviderIsAvailable() {
    $definitions = $this->plugins->getDefinitions();
    $this->assertTrue(isset($definitions['node']), "The node evergreen provider plugin is missing");
  }

  /**
   * Test that all of the views fields are available.
   */
  public function testViewsDataFields() {
    $data = ['node' => []];
    $this->plugin->alterViewsData($data);

    $isset = [
      'evergreen_content',
      'evergreen_expired',
      'is_evergreen',
      'evergreen_expiration',
    ];

    foreach ($isset as $key) {
      $this->assertTrue(isset($data['node'][$key]), "$key is not set");
    }
  }

  public function testViewsDataJoins() {
    $data = ['node' => [], 'evergreen_content' => ['table' => []]];
    $this->plugin->alterViewsData($data);

    $this->assertTrue(isset($data['evergreen_content']['table']['join']['node']));
  }

}
