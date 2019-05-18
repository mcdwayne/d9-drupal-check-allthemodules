<?php

namespace Drupal\pach\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for access control handler plugins.
 *
 * @see \Drupal\pach\Annotation\AccessControlHandler
 * @see \Drupal\pach\AccessControlHandlerManager
 * @see \Drupal\pach\Plugin\AccessControlHandlerBase
 * @see plugin_api
 */
interface AccessControlHandlerInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Whether the handler is applicable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the handler should control access.
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL);

  /**
   * Get the entity type the handler controls access for.
   *
   * @return string
   *   Name of entity type (i.e. "node").
   */
  public function getEntityType();

  /**
   * Checks access to an operation on a given entity or entity translation.
   *
   * Use \Drupal\Core\Entity\EntityAccessControlHandlerInterface::createAccess()
   * to check access to create an entity.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access result to alter by reference.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL);

  /**
   * Checks access to create an entity.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access result to alter by reference.
   * @param string $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   */
  public function createAccess(AccessResultInterface &$access, $entity_bundle = NULL, AccountInterface $account = NULL, array $context = []);

  /**
   * Checks access to an operation on a given entity field.
   *
   * This method does not determine whether access is granted to the entity
   * itself, only the specific field. Callers are responsible for ensuring that
   * entity access is also respected, for example by using
   * \Drupal\Core\Entity\EntityAccessControlHandlerInterface::access().
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The access result to alter by reference.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view" or "edit".
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   (optional) The field values for which to check access, or NULL if access
   *    is checked for the field definition, without any specific value
   *    available. Defaults to NULL.
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface::access()
   */
  public function fieldAccess(AccessResultInterface &$access, $operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL);

}
