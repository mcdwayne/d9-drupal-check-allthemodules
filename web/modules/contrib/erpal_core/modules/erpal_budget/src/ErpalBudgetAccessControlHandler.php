<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\ErpalBudgetAccessControlHandler.
 */

namespace Drupal\erpal_budget;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the erpal_budget entity type.
 *
 * @see \Drupal\erpal_budget\Entity\Node
 */
class ErpalBudgetAccessControlHandler extends EntityAccessControlHandler implements ErpalBudgetAccessControlHandlerInterface {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view erpal_budget entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit erpal_budget entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete erpal_budget entity');
    }
    return AccessResult::allowed();
  }
  
  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add erpal_budget entity');
  }
}
