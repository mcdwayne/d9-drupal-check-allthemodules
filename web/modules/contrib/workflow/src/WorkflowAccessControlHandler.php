<?php

namespace Drupal\workflow;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow\Entity\WorkflowManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the workflow entity type.
 *
 * @see \Drupal\workflow\Entity\Workflow
 * @ingroup workflow_access
 */
class WorkflowAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($entity, $operation, $account, TRUE);

    $account = $this->prepareUser($account);

    // This is only for Edit/Delete transition. For Add/create, use createAccess.
    switch ($entity->getEntityTypeId()) {
      case 'workflow_scheduled_transition':
        switch ($operation) {
          case 'update':
            // This operation is not defined for Scheduled Transitions.
            $result = AccessResult::forbidden();
            break;
          case 'delete':
            // This operation is not defined for Scheduled Transitions.
            $result = AccessResult::forbidden();
            break;
          case 'revert':
            // This operation is not defined for Scheduled Transitions.
            $result = AccessResult::forbidden();
            break;
          default:
            $result = parent::access($entity, $operation, $account, $return_as_object);
            break;
        } // End of switch ($operation).

        break;

      case 'workflow_transition':
        /* @var $transition \Drupal\workflow\Entity\WorkflowTransitionInterface */
        $transition = $entity;
        switch ($operation) {
          case 'update':
            $is_owner = WorkflowManager::isOwner($account, $transition);
            $type_id = $transition->getWorkflowId();
            if ($account->hasPermission("bypass $type_id workflow_transition access")) {
              $result = AccessResult::allowed()->cachePerPermissions();
            }
            elseif ($account->hasPermission("edit any $type_id workflow_transition")) {
              $result = AccessResult::allowed()->cachePerPermissions();
            }
            elseif ($is_owner && $account->hasPermission("edit own $type_id workflow_transition")) {
              $result = AccessResult::allowed()->cachePerPermissions();
            }
            return $return_as_object ? $result : $result->isAllowed();
          case 'delete':
            // The delete operation is not defined for Transitions.
            $result = AccessResult::forbidden();
            break;
          case 'revert':
            // @see workflow_operations.
          default:
            $result = parent::access($entity, $operation, $account, $return_as_object);
            //if ($account->hasPermission("bypass $type_id workflow_transition access")) {
            //  $result = AccessResult::allowed()->cachePerPermissions();
            //}
            break;
        } // End of switch ($operation).

        break;

      case 'workflow_config_transition':
        // This is not (yet) configured.
        break;

      case 'workflow_state':
        switch ($operation) {
          case 'view label':
            // The following two lines are copied from below, and need to be reviewed carefully.
            $result = AccessResult::allowed();
            return $return_as_object ? $result : $result->isAllowed();
          default:
            // E.g., operation 'update' on the WorkflowStates config page.
            break;
        } // End of switch ($operation).

        break;

    }

    /** @var $result AccessResult $result */
//    $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
    $result = $result->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    workflow_debug(__FILE__, __FUNCTION__, __LINE__); // @todo D8-port: still test this snippet.
    /** @var $result AccessResult $result */
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
    $result = $result->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $transition, $operation, AccountInterface $account) {
    return parent::checkAccess($transition, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    workflow_debug(__FILE__, __FUNCTION__, __LINE__); // @todo D8-port: still test this snippet.
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' content'))->cachePerPermissions();
  }

}
