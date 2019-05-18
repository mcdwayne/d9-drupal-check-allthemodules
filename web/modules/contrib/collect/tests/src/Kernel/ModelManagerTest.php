<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Model;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Collect model plugin manager.
 *
 * @group collect
 */
class ModelManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'collect',
    'hal',
    'rest',
    'serialization',
    'collect_common',
  );

  /**
   * Tests config loading.
   */
  public function testLoadConfigByUri() {
    // No models exist. Method call should return default model plugin.
    $this->assertNull(collect_model_manager()->loadModelByUri('anything'));

    // Create three models with different specificity on same URI base. They
    // should be preferred in order of URI pattern, not e.g. ID or label.
    Model::create(array(
      'id' => 'a',
      'label' => 'A',
      'uri_pattern' => 'http://example.com/one',
      'plugin_id' => 'test',
      'processors' => [],
    ))->save();
    Model::create(array(
      'id' => 'b',
      'label' => 'B',
      'uri_pattern' => 'http://example.com/one/two/three/four',
      'plugin_id' => 'test',
      'processors' => [],
    ))->save();
    Model::create(array(
      'id' => 'c',
      'label' => 'C',
      'uri_pattern' => 'http://example.com/one/two',
      'plugin_id' => 'test',
      'processors' => [],
    ))->save();

    $this->assertTrue(collect_model_manager()->loadModelByUri('http://example.com/one/two/three/four/five')->id() == 'b');
    $this->assertTrue(collect_model_manager()->loadModelByUri('http://example.com/one/two/three')->id() == 'c');
    $this->assertNull(collect_model_manager()->loadModelByUri('http://example.com'));
  }

}
