<?php

/**
 * @file
 * Contains \Drupal\log\Access\LogAddAccessCheck.
 */

namespace Drupal\log\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\log\LogTypeInterface;

/**
 * Determines access to for log add pages.
 *
 * @ingroup log_access
 */
class LogAddAccessCheck implements AccessInterface {

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
   * Checks access to the log add page for the log type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\log\LogTypeInterface $log_type
   *   (optional) The log type. If not specified, access is allowed if there
   *   exists at least one log type for which the user may create a log.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, LogTypeInterface $log_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('log');
    // If checking whether a log of a particular type may be created.
    if ($account->hasPermission('administer log types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($log_type) {
      return $access_control_handler->createAccess($log_type->id(), $account, [], TRUE);
    }
    // If checking whether a log of any type may be created.
    foreach ($this->entityManager->getStorage('log_type')->loadMultiple() as $log_type) {
      if (($access = $access_control_handler->createAccess($log_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }
    // No opinion.
    return AccessResult::neutral();
  }

}
