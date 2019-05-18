<?php

namespace Drupal\Tests\media_entity_dreambroker\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_entity_dreambroker\Plugin\media\Source\Dreambroker;

/**
 * Tests thumbnail generation for Dream Broker responses.
 *
 * @group media_entity_dreambroker
 */
class ThumbnailTest extends KernelTestBase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\media_entity_dreambroker\Plugin\media\Source\Dreambroker
   */
  protected $plugin;

  /**
   * A dreambroker media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'file',
    'image',
    'media',
    'media_entity_dreambroker',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installConfig(['media_entity_dreambroker', 'system']);

    MediaType::create([
      'id' => 'dreambroker',
      'source' => 'dreambroker',
      'source_configuration' => [
        'source_field' => 'dreambroker',
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'dreambroker',
      'entity_type' => 'media',
      'type' => 'string_long',
    ])->save();

    FieldConfig::create([
      'field_name' => 'dreambroker',
      'entity_type' => 'media',
      'bundle' => 'dreambroker',
    ])->save();

    $this->entity = Media::create([
      'bundle' => 'dreambroker',
      'dreambroker' => 'https://www.dreambroker.com/channel/1zcdkjfg/h8q6cakv',
    ]);

    $this->plugin = Dreambroker::create(
      $this->container,
      MediaType::load('dreambroker')->get('source_configuration'),
      'dreambroker',
      MediaType::load('dreambroker')->getSource()->getPluginDefinition()
    );

    $dir = $this->container
      ->get('config.factory')
      ->get('media_entity_dreambroker.settings')
      ->get('local_images');

    file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  }

  /**
   * Tests that an existing local image is used as the thumbnail.
   */
  public function testLocalImagePresent() {
    $uri = 'public://dreambroker-thumbnails/h8q6cakv.png';
    touch($uri);
    $this->assertEquals($uri, $this->plugin->getMetadata($this->entity, 'thumbnail_uri'));
  }

  /**
   * Tests that a local image is downloaded if available but not present.
   */
  public function testLocalImageNotPresent() {
    $uri = 'public://dreambroker-thumbnails/h8q6cakv.png';
    touch($uri);
    file_unmanaged_delete($uri);

    $this->plugin->getMetadata($this->entity, 'thumbnail_uri');
    $this->assertFileExists('public://dreambroker-thumbnails/h8q6cakv.png');
  }

  /**
   * Tests that the default thumbnail is used if no local image is available.
   */
  public function testNoLocalImage() {
    $this->entity->set('dreambroker', 'https://www.dreambroker.com/channel/1zcdkjfg/h8q6cakk');
    $this->assertEquals(
      '/dreambroker.png',
      $this->plugin->getMetadata($this->entity, 'thumbnail_uri')
    );
  }

}
