<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\Access\ErpalBudgetAddAccessCheck.
 */

namespace Drupal\erpal_budget\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\erpal_budget\ErpalBudgetTypeInterface;

/**
 * Determines access to for node add pages.
 */
class ErpalBudgetAddAccessCheck implements AccessInterface {

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
   * Checks access to the ERPAL Budget add page for the erpal budget type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\erpal_budget\ErpalBudgetTypeInterface $erpal_budget_type
   *   (optional) The node type. If not specified, access is allowed if there
   *   exists at least one node type for which the user may create a node.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, ErpalBudgetTypeInterface $erpal_budget_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('erpal_budget');
    // If checking whether a node of a particular type may be created.
    if ($erpal_budget_type) {
      return $access_control_handler->createAccess($erpal_budget_type->id(), $account, [], TRUE);
    }

    // If checking whether a node of any type may be created.
    foreach ($this->entityManager->getStorage('erpal_budget_type')->loadMultiple() as $erpal_budget_type) {
      if (($access = $access_control_handler->createAccess($erpal_budget_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
