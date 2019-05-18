<?php

namespace Drupal\entity_gallery\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_gallery\EntityGalleryInterface;

/**
 * Determines access to entity gallery previews.
 *
 * @ingroup entity_gallery_access
 */
class EntityGalleryPreviewAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the entity gallery preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery_preview
   *   The entity gallery that is being previewed.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, EntityGalleryInterface $entity_gallery_preview) {
    if ($entity_gallery_preview->isNew()) {
      $access_controller = $this->entityManager->getAccessControlHandler('entity_gallery');
      return $access_controller->createAccess($entity_gallery_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $entity_gallery_preview->access('update', $account, TRUE);
    }
  }

}
