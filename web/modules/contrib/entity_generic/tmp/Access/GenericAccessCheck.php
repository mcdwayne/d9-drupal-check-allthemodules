<?php

namespace Drupal\entity_generic\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\entity_generic\Entity\GenericTypeInterface;

/**
 * Checks access to add, edit and delete entities.
 */
class GenericAccessCheck implements AccessInterface {

  /**
 * The entity manager.
 *
 * @var \Drupal\Core\Entity\EntityTypeManagerInterface
 */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the entity add page for the entity type.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\entity_generic\Entity\GenericTypeInterface $entity_generic_type
   *   The type entity.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GenericTypeInterface $entity_generic_type = NULL) {
    if(!$entity_generic_type) {
      $route_parameters = array_keys($route->getOption('parameters'));
      $entity_generic_type_id = $route_parameters[0];
      $entity_generic_type = $this->entityTypeManager->getDefinition($entity_generic_type_id);
    }
    $entity_generic_id = $entity_generic_type->get('bundle_of');
    $entity_generic = $this->entityTypeManager->getDefinition($entity_generic_id);
    $entity_generic_string = $entity_generic->get('additional')['entity_generic']['names']['base'];
    $entity_generic_admin_permission = $entity_generic->get('admin_permission');

    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_generic->id());

    if ($account->hasPermission('bypass ' . $entity_generic_string . ' access')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission($entity_generic_admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $operation = $route->getOption('operation');

    if ($operation == 'add') {
      return $access_control_handler->createAccess($entity_generic_type->id(), $account, [], TRUE);
    }

    // If checking whether an entity of any type may be created.
    foreach ($this->entityTypeManager->getStorage($entity_generic_type->id())->loadMultiple() as $entity_generic_type) {
      if (($access = $access_control_handler->createAccess($entity_generic_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }
}