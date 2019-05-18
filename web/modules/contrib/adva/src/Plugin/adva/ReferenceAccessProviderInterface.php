<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Extends the AccessProvider definition to provide a field api layer.
 */
interface ReferenceAccessProviderInterface extends AccessProviderInterface {

  /**
   * Get Reference Target type.
   *
   * Returns the target type for this reference provider, to be used to locate
   * fields that apply to this provider.
   *
   * @retun string
   *   Entity type id.
   */
  public static function getTargetType();

  /**
   * Get Enabled Reference fields with a given type.
   *
   * Compiles a list of field_names that are attached to a given entity type, or
   * one of its bundle types, that have a specified target type.
   *
   * @param string $entity_type_id
   *   Base entity type fields are attached to.
   * @param string $target_type_id
   *   Target type to filter entity reference fields by.
   *
   * @return string[]
   *   List of fields names of entity reference fields with the specified type.
   */
  public static function getReferenceFields($entity_type_id, $target_type_id);

  /**
   * Get Enabled Reference fields with a given type.
   *
   * Compiles a list of field_names that are attached to a given entity bundle
   * type that have a specified target type.
   *
   * @param string $entity_type_id
   *   Base entity type fields are attached to.
   * @param string $bundle
   *   Base entity bundle fields are attached to.
   * @param string $target_type_id
   *   Target type to filter entity reference fields by.
   *
   * @return string[]
   *   List of fields names of entity reference fields with the specified type.
   */
  public static function getBundleReferenceFields($entity_type_id, $bundle, $target_type_id);

  /**
   * Get Entity Type manager service.
   *
   * @retun \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager.
   */
  public function getEntityTypeManager();

  /**
   * Get Enabled Reference fields that are enabled for this provider.
   *
   * Compiles a list of field_names that should be used to restrict access to
   * this items controlled by this provider.
   *
   * @param string $entity_type_id
   *   Base entity type fields are attached to.
   * @param string $bundle
   *   Base entity bundle fields are attached to.
   *
   * @return string[]
   *   List of fields names of entity reference fields with the specified type.
   */
  public function getEnabledBundleFields($entity_type_id, $bundle = NULL);

  /**
   * Get referenced entities for access control by the provider.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Source entity to find relations for.
   *
   * @return string[]
   *   Key value array where keys are the field names and values are the entity
   *   ids referenced by that field.
   */
  public function getReferencedItems(EntityInterface $entity);

  /**
   * Gets a list of authorized entity ids that an account.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return arrays
   *   An array of string or integer ids for entities which this provider should
   *   grant access based on.
   */
  public function getAuthorizedEntityIds($operation, AccountInterface $account);

}
