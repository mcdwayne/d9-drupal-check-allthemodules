<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_gallery\Entity\EntityGallery;
use Drupal\entity_gallery\Entity\EntityGalleryType;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the Entity Gallery entity's cache tags.
 *
 * @group entity_gallery
 */
class EntityGalleryCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('entity_gallery');

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" entity gallery type.
    EntityGalleryType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ])->save();

    // Create a "Llama" entity gallery.
    $entity_gallery = EntityGallery::create(['type' => 'camelids']);
    $entity_gallery->setTitle('Llama')
      ->setPublished(TRUE)
      ->save();

    return $entity_gallery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity) {
    return ['timezone'];
  }

  /**
   * {@inheritdoc}
   *
   * Each entity gallery must have an author.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity_gallery) {
    return array('user:' . $entity_gallery->getOwnerId());
  }

}
