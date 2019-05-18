<?php

namespace Drupal\entity_gallery\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_gallery\EntityGalleryTypeInterface;

/**
 * Determines access to for entity gallery add pages.
 *
 * @ingroup entity_gallery_access
 */
class EntityGalleryAddAccessCheck implements AccessInterface {

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
   * Checks access to the entity gallery add page for the entity gallery type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\entity_gallery\EntityGalleryTypeInterface $entity_gallery_type
   *   (optional) The entity gallery type. If not specified, access is allowed
   *   if there exists at least one entity gallery type for which the user may
   *   create an entity gallery.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, EntityGalleryTypeInterface $entity_gallery_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('entity_gallery');
    // If checking whether an entity gallery of a particular type may be
    // created.
    if ($account->hasPermission('administer entity gallery types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($entity_gallery_type) {
      return $access_control_handler->createAccess($entity_gallery_type->id(), $account, [], TRUE);
    }
    // If checking whether an entity gallery of any type may be created.
    foreach ($this->entityManager->getStorage('entity_gallery_type')->loadMultiple() as $entity_gallery_type) {
      if (($access = $access_control_handler->createAccess($entity_gallery_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
