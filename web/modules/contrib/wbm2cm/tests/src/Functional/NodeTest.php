<?php

namespace Drupal\Tests\wbm2cm\Functional;

/**
 * @group wbm2cm
 */
class NodeTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('node');

    $this->moderate($this->drupalCreateContentType())->save();
    $this->moderate($this->drupalCreateContentType())->save();
  }

}
