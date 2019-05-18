<?php

namespace Drupal\entity_gallery\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_gallery\EntityGalleryInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for entity gallery revisions.
 *
 * @ingroup entity_gallery_access
 */
class EntityGalleryRevisionAccessCheck implements AccessInterface {

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\entity_gallery\EntityGalleryStorageInterface
   */
  protected $entityGalleryStorage;

  /**
   * The entity gallery access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $entityGalleryAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = array();

  /**
   * Constructs a new EntityGalleryRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityGalleryStorage = $entity_manager->getStorage('entity_gallery');
    $this->entityGalleryAccess = $entity_manager->getAccessControlHandler('entity_gallery');
  }

  /**
   * Checks routing access for the entity gallery revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $entity_gallery_revision
   *   (optional) The entity gallery revision ID. If not specified, but
   *   $entity_gallery is, access is checked for that object's revision.
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   (optional) A entity gallery object. Used for checking access to an entity
   *   gallery's default revision when $entity_gallery_revision is unspecified.
   *   Ignored when $entity_gallery_revision is specified. If neither
   *   $entity_gallery_revision nor $entity_gallery are specified, then access
   *   is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $entity_gallery_revision = NULL, EntityGalleryInterface $entity_gallery = NULL) {
    if ($entity_gallery_revision) {
      $entity_gallery = $this->entityGalleryStorage->loadRevision($entity_gallery_revision);
    }
    $operation = $route->getRequirement('_access_entity_gallery_revision');
    return AccessResult::allowedIf($entity_gallery && $this->checkAccess($entity_gallery, $account, $operation))->cachePerPermissions()->addCacheableDependency($entity_gallery);
  }

  /**
   * Checks entity gallery revision access.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   The entity gallery to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view.'
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(EntityGalleryInterface $entity_gallery, AccountInterface $account, $op = 'view') {
    $map = array(
      'view' => 'view all revisions',
      'update' => 'revert all revisions',
      'delete' => 'delete all revisions',
    );
    $bundle = $entity_gallery->bundle();
    $type_map = array(
      'view' => "view $bundle revisions",
      'update' => "revert $bundle revisions",
      'delete' => "delete $bundle revisions",
    );

    if (!$entity_gallery || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no entity gallery to check against, or the $op was not one
      // of the supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $entity_gallery->language()->getId();
    $cid = $entity_gallery->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($type_map[$op]) && !$account->hasPermission('administer entity galleries')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // There should be at least two revisions. If the vid of the given entity
      // gallery and the vid of the default revision differ, then we already
      // have two different revisions so there is no need for a separate
      // database check. Also, if you try to revert to or delete the default
      // revision, that's not good.
      if ($entity_gallery->isDefaultRevision() && ($this->entityGalleryStorage->countDefaultLanguageRevisions($entity_gallery) == 1 || $op == 'update' || $op == 'delete')) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer entity galleries')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // entity gallery passed in is not the default revision then access to
        // that, too.
        $this->access[$cid] = $this->entityGalleryAccess->access($this->entityGalleryStorage->load($entity_gallery->id()), $op, $account) && ($entity_gallery->isDefaultRevision() || $this->entityGalleryAccess->access($entity_gallery, $op, $account));
      }
    }

    return $this->access[$cid];
  }

}
