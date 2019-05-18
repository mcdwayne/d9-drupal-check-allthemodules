<?php

namespace Drupal\mailing_list\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mailing_list\MailingListInterface;

/**
 * Determines access to for subscription add pages.
 */
class SubscriptionAddAccessCheck implements AccessInterface {

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
   * Checks access to the subscription add page for the mailing list.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\mailing_list\MailingListInterface $mailing_list
   *   (optional) The mailing list. If not specified, access is allowed if there
   *   exists at least one mailing list for which the user may create a node.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, MailingListInterface $mailing_list = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('mailing_list_subscription');
    // If checking whether a subscription of a particular list may be created.
    if ($account->hasPermission('administer mailing list subscriptions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($mailing_list) {
      return $access_control_handler->createAccess($mailing_list->id(), $account, [], TRUE);
    }

    // If checking whether a subscription for any mailing list may be created.
    foreach ($this->entityManager->getStorage('mailing_list')->loadMultiple() as $mailing_list) {
      if (($access = $access_control_handler->createAccess($mailing_list->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
