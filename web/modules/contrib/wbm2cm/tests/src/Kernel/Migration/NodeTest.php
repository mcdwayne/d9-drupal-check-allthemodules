<?php

namespace Drupal\Tests\wbm2cm\Kernel\Migration;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the save-clear-restore migration flow for nodes.
 *
 * @group wbm2cm
 */
class NodeTest extends TestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->storage = $this->container->get('entity_type.manager')
      ->getStorage('node');

    $this->createContentType();
  }

}
