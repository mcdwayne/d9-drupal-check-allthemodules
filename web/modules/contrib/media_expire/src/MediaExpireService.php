<?php

namespace Drupal\media_expire;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Contains the media unpublish logic.
 *
 * @package Drupal\media_expire
 */
class MediaExpireService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the MediaExpireService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Unpublishes already expired media elements.
   */
  public function unpublishExpiredMedia() {

    /** @var \Drupal\media\MediaTypeInterface[] $bundles */
    $bundles = $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple();

    foreach ($bundles as $bundle) {
      if ($bundle->getThirdPartySetting('media_expire', 'enable_expiring')) {

        $expireField = $bundle->getThirdPartySetting('media_expire', 'expire_field');
        $query = $this->entityTypeManager->getStorage('media')->getQuery('AND');
        $query->condition('status', 1);
        $query->condition('bundle', $bundle->id());
        $query->condition($expireField, date("Y-m-d\TH:i:s"), '<');
        $entityIds = $query->execute();

        /** @var \Drupal\media\Entity\Media[] $medias */
        $medias = $this->entityTypeManager->getStorage('media')
          ->loadMultiple($entityIds);

        foreach ($medias as $media) {
          $media->setUnpublished();
          $media->$expireField->removeItem(0);
          $media->save();
        }
      }
    }
  }

}
