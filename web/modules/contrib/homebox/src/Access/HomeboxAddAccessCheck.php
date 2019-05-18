<?php

namespace Drupal\homebox\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\homebox\Entity\HomeboxInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for reference add pages.
 *
 * @ingroup reference_access
 */
class HomeboxAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the reference add page for the reference type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\homebox\Entity\HomeboxInterface $homebox
   *   (optional) The homebox.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, HomeboxInterface $homebox = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('homebox_layout');
    // If checking whether a reference of a particular type may be created.
    if ($account->hasPermission('administer homebox_layout')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($homebox) {
      return $access_control_handler->createAccess($homebox->id(), $account, [], TRUE);
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
