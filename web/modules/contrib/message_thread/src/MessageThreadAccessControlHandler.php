<?php

namespace Drupal\message_thread;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class MessageThreadAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    // Return early if we have bypass or create any template permissions.
    if ($account->hasPermission('bypass message thread access control') || $account->hasPermission($operation . ' any message thread template')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $params = [$entity, $operation, $account];

    /** @var \Drupal\Core\Access\AccessResult[] $results */
    $results = $this
      ->moduleHandler()
      ->invokeAll('message_thread_access_control', [$params]);

    foreach ($results as $result) {
      if ($result->isNeutral()) {
        continue;
      }

      return $result;
    }

    $current_id = $account->id();
    $allow = [];
    // Allow all participants to view.
    if ($operation == 'view' && $entity->get('field_thread_participants')->getValue() != NULL) {
      if (AccessResult::allowedIfHasPermission($account, 'view own messages')) {
        $participants = $entity->get('field_thread_participants')->getValue();
        foreach ($participants as $participant) {
          if (!isset($participant['target_id'])) {
            continue;
          }
          $allow[] = $participant['target_id'];
        }
      }
    }
    // Allow author of thread to edit and delete.
    if ($entity->get('uid')->getValue() != NULL) {
      switch ($operation) {

        case 'view':
          $access_result = AccessResult::allowedIfHasPermission($account, 'view own private messages');
          if ($access_result instanceof AccessResultAllowed) {
            $allow[] = $entity->get('uid')->getValue()[0]['target_id'];
          }
          break;

        case 'edit':
          $access_result = AccessResult::allowedIfHasPermission($account, 'edit own private messages');
          if ($access_result instanceof AccessResultAllowed) {
            $allow[] = $entity->get('uid')->getValue()[0]['target_id'];
          }
          break;

        case 'delete':
          $access_result = AccessResult::allowedIfHasPermission($account, 'delete own private messages');
          if ($access_result instanceof AccessResultAllowed) {
            $allow[] = $entity->get('uid')->getValue()[0]['target_id'];
          }
          break;

      }
    }

    if (in_array($current_id, $allow)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Return early if we have bypass or create any template permissions.
    if ($account->hasPermission('bypass message thread access control') || $account->hasPermission('create any message template')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\Core\Access\AccessResult[] $results */
    $results = $this->moduleHandler()->invokeAll('message_thread_create_access_control', [
      $entity_bundle,
      $account,
    ]);

    foreach ($results as $result) {
      if ($result->isNeutral()) {
        continue;
      }

      // We only return this if a result is not neutral,
      // meaning that this hook overrides the default.
      return $result;
    }

    // When we have a bundle, check access on that bundle.
    if ($entity_bundle) {
      return AccessResult::allowedIfHasPermission($account, 'create and receive ' . $entity_bundle . ' message threads')
        ->cachePerPermissions();
    }

    // With no bundle, e.g. on message thread/add,
    // check access to any message thread bundle.
    // @todo: perhaps change this method to a service as in NodeAddAccessCheck.
    foreach (\Drupal::entityManager()->getStorage('message_thread_template')->loadMultiple() as $template) {
      $access = AccessResult::allowedIfHasPermission($account, 'create and receive ' . $template->id() . ' message threads');

      // If access is allowed to any of the existing bundles return allowed.
      if ($access->isAllowed()) {
        return $access->cachePerPermissions();
      }
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

}
