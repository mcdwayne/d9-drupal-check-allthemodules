<?php

namespace Drupal\Tests\wbm2cm\Functional;

use Drupal\block_content\Entity\BlockContentType;

/**
 * @group wbm2cm
 */
class BlockContentTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('block_content');

    /** @var \Drupal\block_content\BlockContentTypeInterface $block_content_type */
    $block_content_type = BlockContentType::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    ]);

    $this->moderate($block_content_type)->save();
  }

}
