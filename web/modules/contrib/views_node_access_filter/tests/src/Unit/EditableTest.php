<?php

namespace Drupal\Tests\views_node_access_filter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views_node_access_filter\Plugin\views\filter\Editable;

/**
 * @group views_node_access_filter
 */
class EditableTest extends UnitTestCase {

  /**
   * @var Editable
   */
  private $plugin;

  protected function setUp() {
    parent::setUp();
    $this->plugin = new Editable([], 'foo', []);
  }

  /**
   * @expectedException \Exception
   */
  public function testQuery() {
    $this->assertNull($this->plugin->query());
  }

  public function testGetCacheContexts() {
    $this->assertContains('user', $this->plugin->getCacheContexts());
  }

  public function testCanExpose() {
    $this->assertFalse($this->plugin->canExpose());
  }

}
