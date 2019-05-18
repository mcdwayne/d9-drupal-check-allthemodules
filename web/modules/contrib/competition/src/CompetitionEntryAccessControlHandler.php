<?php

namespace Drupal\competition;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Competition entry entity.
 *
 * @see \Drupal\competition\Entity\CompetitionEntry.
 */
class CompetitionEntryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Performs create access checks.
   *
   * Generally, user can access entry creation form if they have
   * 'access content' perm or the administrative perm for the entry entity type.
   *
   * NOTE: additional "access checks" are performed in the controller callback
   * instead of here. These allow helpful redirections instead of 403s.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param array $context
   *   An array of key-value pairs to pass additional context when needed.
   * @param string|null $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\competition\Controller\CompetitionEntryController::getForm()
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {

    // 2017-02-14, Tory:
    // This permission used to be the only access control applied to the
    // 'entity.competition_entry.add_form' route. Port it here.
    $result = AccessResult::allowedIfHasPermission($account, 'access content');

    if ($admin_permission = $this->entityType->getAdminPermission()) {
      $result->orIf(AccessResult::allowedIfHasPermission($account, $admin_permission));
    }

    return $result;

  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entry, $operation, AccountInterface $account) {
    $result = AccessResult::forbidden();
    // Get the competition the entry belongs to.
    $competition = \Drupal::entityTypeManager()
      ->getStorage($entry->getEntityType()->getBundleEntityType())
      ->load($entry->bundle());
    switch ($operation) {
      case 'update':
        // Get current competition's entry limit.
        $limits = $competition
          ->getEntryLimits();

        if ($account->hasPermission('administer competition entries')) {
          $result = AccessResult::allowed();
        }
        elseif ($entry->getOwnerId() == $account->id()) {
          if ($competition->getStatus() == CompetitionInterface::STATUS_OPEN) {
            if (!empty($limits->require_user) && !empty($limits->allow_partial_save)) {
              // User may update entry as long as it is not Finalized.
              // Note: technically, this is the correct place to disallow
              // access...but just throwing a 403 at user in this case is not
              // great; instead just allow passthrough here, and check
              // STATUS_FINALIZED in the form callback itself.
              // @see CompetitionEntryForm::buildForm()
              $result = AccessResult::allowed();
            }
            else {
              $result = AccessResult::allowed();
            }
          }
        }
        break;

      case 'delete':
        if ($account->hasPermission('administer competition entries')) {
          $result = AccessResult::allowed();
        }
        break;

      case 'view':
        $archived_cycles = $competition->getCyclesArchived();
        if ($account->hasPermission('administer competition entries')) {
          $result = AccessResult::allowed();
        }
        elseif ($entry->getOwnerId() == $account->id()) {
          $result = AccessResult::allowed();
        }
        elseif ($entry->getStatus() == CompetitionEntryInterface::STATUS_ARCHIVED && in_array($entry->getCycle(), $archived_cycles)) {
          $result = AccessResult::allowed();
        }
        break;
    }

    return $result;
  }

}
