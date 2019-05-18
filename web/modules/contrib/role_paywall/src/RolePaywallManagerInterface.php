<?php

namespace Drupal\role_paywall;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface RolePaywallManagerInterface.
 */
interface RolePaywallManagerInterface {

  /**
   * Registers a node to be behind the paywall.
   *
   * @param int $id
   *   The node id to add to the array
   */
  public function addPaywallNode($id);

  /**
   * Gets the array with node ids of the nodes behind the paywall.
   *
   * @return array
   *   Array with all node ids
   */
  public function getPaywallNodes();

  /**
   * Gets all the role names that can bypass the paywall.
   *
   * @return array
   *   An array with all role names
   */
  public function getPaywallRoles();

  /**
   * Gets all the content bundles affected by the paywall.
   *
   * @return array
   *   An array with all the bundle keys
   */
  public function getPaywallBundles();

  /**
   * Gets the block id to be used as a paywall barrier.
   *
   * This method can be hooked by hook_paywall_barrier_alter().
   *
   * @return int
   *   The id of the block to be used ans barrier
   */
  public function getBarrier();

  /**
   * Helper method for checking paywall access conditions on regular fields.
   *
   * @param string $operation
   *   The operation name.
   * @param FieldDefinitionInterface $field_definition
   *   The definiton of the field to check access for.
   * @param AccountInterface $account
   *   The user account to check the access.
   * @param FieldItemListInterface $items
   *   The entity field object where the check is performed.
   *
   * @return AccessResult
   *   The AccessResult entity for the check.
   */
  public function getFieldAccessResult($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL);

  /**
   * Helper method for checking paywall access conditions on regular fields.
   *
   * @param string $operation
   *   The operation name.
   * @param string $field_name
   *   The field name to check access for.
   * @param string $target_bundle
   *   The bundle of the field to be checked.
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return AccessResult
   *   The AccessResult entity for the check.
   */
  public function getExtraFieldAccessResult($operation, $field_name, $target_bundle, $entity);

  /**
   * Determinates the AccessResult for a given field in the specific context.
   *
   * @param string $operation
   *   The operation name.
   * @param string $field_name
   *   The field name to check access for.
   * @param string $target_bundle
   *   The bundle of the field to be checked.
   * @param AccountInterface $account
   *   The user account to check the access.
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return AccessResult
   *   The AccessResult entity for the check.
   */
  public function getAccessResult($operation, $field_name, $target_bundle, AccountInterface $account, EntityInterface $entity);

}
