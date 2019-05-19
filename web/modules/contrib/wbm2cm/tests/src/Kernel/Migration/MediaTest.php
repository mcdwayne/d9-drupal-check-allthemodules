<?php

namespace Drupal\Tests\wbm2cm\Kernel\Migration;

use Drupal\media_entity\Entity\MediaBundle;

/**
 * Tests the save-clear-restore migration flow for media items.
 *
 * @group wbm2cm
 */
class MediaTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'field',
    'file',
    'image',
    'media_entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('media_entity');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installSchema('file', 'file_usage');

    $this->storage = $this->container->get('entity_type.manager')
      ->getStorage('media');

    MediaBundle::create([
      'id' => 'generic',
      'label' => $this->randomMachineName(),
    ])->save();
  }

}
