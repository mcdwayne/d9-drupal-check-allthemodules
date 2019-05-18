<?php

namespace Drupal\Tests\evergreen_node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\evergreen\Entity\EvergreenConfig;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests the NodeIsEvergreen views filter.
 *
 * @group evergreen_node
 * @SuppressWarnings(StaticAccess)
 */
class NodeIsEvergreenViewsFilterTest extends ViewsKernelTestBase {

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
    'evergreen_node_views_test',
  ];

  public static $testViews = ['evergreen_node', 'evergreen_node2'];

  /**
   * Setup.
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installConfig(['system', 'views', 'evergreen_node', 'evergreen_node_views_test']);
    $install_schemas = ['user', 'node', 'evergreen_content'];
    foreach ($install_schemas as $schema) {
      $this->installEntitySchema($schema);
    }

    $this->service = \Drupal::service('evergreen');
    $this->plugins = \Drupal::service('plugin.manager.evergreen');
    $this->plugin = $this->plugins->createInstance('node');

    ViewTestData::createTestViews(get_class($this), ['evergreen_node_views_test']);

    $node_type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $node_type = NodeType::create(['type' => 'blog', 'name' => 'blog']);
    $node_type->save();
  }

  /**
   * Create evergreen configuration.
   */
  protected function createEvergreenConfig(array $options = []) {
    $defaults = [
      'bundle' => 'page',
      'status' => EVERGREEN_STATUS_EVERGREEN,
    ];
    $options = array_merge($defaults, $options);
    $config = EvergreenConfig::create([
      'id' => 'node.' . $options['bundle'],
      'evergreen_entity_type' => 'node',
      'evergreen_bundle' => $options['bundle'],
      'evergreen_default_status' => $options['status'],
    ]);
    $config->save();
    return $config;
  }

  /**
   * Create a node.
   */
  protected function createNode(array $options = []) {
    $defaults = [
      'type' => 'page',
      'title' => 'Node 1',
    ];
    $options = array_merge($defaults, $options);
    $node = Node::create($options);
    $node->save();
    return $node;
  }

  /**
   * Get the view and execute it.
   */
  protected function getViewAndExecute($view_name = 'evergreen_node') {
    $view = Views::getView($view_name);
    $view->setDisplay();
    $view->execute();
    return $view;
  }

  /**
   * Test default empty view.
   */
  public function testFilterEmpty() {
    $this->createEvergreenConfig();
    $view = $this->getViewAndExecute();
    $this->assertCount(0, $view->result);
  }

  /**
   * Test NodeIsEvergreen views filter with evergreen-default content.
   */
  public function testFilterWithDefaultEvergreenOnly() {
    $this->createEvergreenConfig();

    // create a node without a content entity so it will use the default
    $this->createNode();

    $view = $this->getViewAndExecute();
    $this->assertCount(1, $view->result, 'There is one node in the system, so it should be returned (evergreen by default)');
  }

  /**
   * Test NodeIsEvergreen views filter with evergreen-default content.
   */
  public function testFilterWithDefaultEvergreenOnlyAndContentEntity() {
    $config = $this->createEvergreenConfig();

    // create a node without a content entity so it will use the default
    $node = $this->createNode();

    // now add a content entity for the node
    $content = $this->service->getContent($node, $config);
    $content->save();

    $view = $this->getViewAndExecute();
    $this->assertCount(1, $view->result, 'There is one node in the system, so it should be returned (evergreen by content entity)');
  }

  /**
   * Test NodeIsEvergreen views filter with evergreen-default content.
   */
  public function testFilterWithDefaultEvergreenOnlyAndPerishable() {
    $config = $this->createEvergreenConfig();

    // create a node without a content entity so it will use the default
    $node = $this->createNode();

    // now add a content entity for the node
    $content = $this->service->getContent($node, $config);
    $content->set('evergreen_status', 0);
    $content->save();

    $view = $this->getViewAndExecute();
    $this->assertCount(0, $view->result, 'There is one node in the system but it should not be returned (perishable by content entity)');
  }

  /**
   * Test NodeIsEvergreen views filter with expires-default content.
   */
  public function testFilterWithDefaultExpiresOnly() {
    $this->createEvergreenConfig(['status' => 0]);

    // create a node without a content entity so it will use the default
    $this->createNode();

    $view = $this->getViewAndExecute();
    $this->assertCount(0, $view->result, 'There is one node in the system, but it is perishable by default');
  }

  /**
   * Test NodeIsEvergreen views filter with expires-default content.
   */
  public function testFilterWithDefaultExpiresOnlyAndContentEntity() {
    $config = $this->createEvergreenConfig(['status' => 0]);

    // create a node without a content entity so it will use the default
    $node = $this->createNode();

    // now add a content entity for the node
    $content = $this->service->getContent($node, $config);
    $content->save();

    $view = $this->getViewAndExecute();
    $this->assertCount(1, $view->result, "There is one node in the system, but it is perishable by it's content entity");
  }

  /**
   * Test NodeIsEvergreen views filter with expires-default content.
   */
  public function testFilterWithDefaultExpiresOnlyAndPerishable() {
    $config = $this->createEvergreenConfig(['status' => 0]);

    // create a node without a content entity so it will use the default
    $node = $this->createNode();

    // now add a content entity for the node
    $content = $this->service->getContent($node, $config);
    $content->set('evergreen_status', EVERGREEN_STATUS_EVERGREEN);
    $content->save();

    $view = $this->getViewAndExecute();
    $this->assertCount(1, $view->result, "There is one node in the system and it is evergreen by it's content entity");
  }

  /**
   * Test NodeIsEvergreen views filter with mixed content.
   *
   * This tests a case where we have two evergreen content types with content.
   * Neither node has a content entity, so they will use their defaults, meaning
   */
  public function testFilterWithMixedContentDefaultsOnly() {
    // page will default evergreen
    $this->createEvergreenConfig(['status' => EVERGREEN_STATUS_EVERGREEN]);

    // blog will default perishable
    $this->createEvergreenConfig(['bundle' => 'blog', 'status' => 0]);

    // create a page node without a content entity so it will use the default
    $this->createNode();
    $this->createNode(['type' => 'blog', 'title' => 'Blog 1']);

    $view = $this->getViewAndExecute();
    $this->assertCount(1, $view->result, 'There are two nodes in the system, but one is perishable by default and should not show up');
  }

  /**
   * Test NodeIsEvergreen views filter with mixed content.
   *
   * This tests a case where we have two evergreen content types with content.
   * Both nodes have a content entity set to evergreen so they should show up.
   */
  public function testFilterWithMixedContent() {
    // page will default evergreen
    $this->createEvergreenConfig(['status' => EVERGREEN_STATUS_EVERGREEN]);

    // blog will default perishable
    $blog_config = $this->createEvergreenConfig(['bundle' => 'blog', 'status' => 0]);

    // create a page node without a content entity so it will use the default
    $this->createNode();
    $blog = $this->createNode(['type' => 'blog', 'title' => 'Blog 1']);

    $content = $this->service->getContent($blog, $blog_config);
    $content->set('evergreen_status', EVERGREEN_STATUS_EVERGREEN);
    $content->save();

    $view = $this->getViewAndExecute('evergreen_node2');
    $this->assertCount(2, $view->result, 'There are two nodes in the system and both should show up');
  }

  /**
   * Test NodeIsEvergreen views filter with mixed content.
   *
   * Tests the same as testFilterWithMixedContent, but with the blog content
   * marked as perishable.
   */
  public function testFilterWithMixedContent2() {
    // page will default evergreen
    $this->createEvergreenConfig(['status' => EVERGREEN_STATUS_EVERGREEN]);

    // blog will default perishable
    $blog_config = $this->createEvergreenConfig(['bundle' => 'blog', 'status' => 0]);

    // create a page node without a content entity so it will use the default
    $this->createNode();
    $blog = $this->createNode(['type' => 'blog', 'title' => 'Blog 1']);

    $content = $this->service->getContent($blog, $blog_config);
    $content->set('evergreen_status', 0);
    $content->save();

    $view = $this->getViewAndExecute('evergreen_node2');
    $this->assertCount(1, $view->result, 'There are two nodes in the system and only the page should show up');
  }

}
