<?php

namespace Drupal\Tests\wbm2cm\Kernel\Migration;

use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests the save-clear-restore migration flow for block content.
 *
 * @group wbm2cm
 */
class BlockContentTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'field',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('block_content');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');

    $this->storage = $this->container->get('entity_type.manager')
      ->getStorage('block_content');

    BlockContentType::create([
      'id' => 'fubar',
      'label' => $this->randomMachineName(),
    ])->save();
  }

}
