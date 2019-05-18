<?php

/**
 * @file
 * Contains \Drupal\entity_base\Access\EntityBaseAccessCheck.
 */
namespace Drupal\entity_base\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\entity_base\Entity\EntityBaseTypeInterface;


/**
 * Checks access to add, edit and delete entities.
 */
class EntityBaseAccessCheck implements AccessInterface {

  /**
 * The entity manager.
 *
 * @var \Drupal\Core\Entity\EntityTypeManagerInterface
 */
  protected $entityTypeManager;

  /**
   * Constructs a EntityBaseAccessCheck object.
   *
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
   * @param \Drupal\entity_base\Entity\EntityBaseTypeInterface $entity_base_type
   *   The type entity.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, EntityBaseTypeInterface $entity_base_type = NULL) {
    if(!$entity_base_type) {
      $route_parameters = array_keys($route->getOption('parameters'));
      $entity_base_type_id = $route_parameters[0];
      $entity_base_type = $this->entityTypeManager->getDefinition($entity_base_type_id);
    }
    $entity_base_id = $entity_base_type->get('bundle_of');
    $entity_base = $this->entityTypeManager->getDefinition($entity_base_id);
    $entity_base_string = $entity_base->get('additional')['entity_base']['names']['base'];
    $entity_base_admin_permission = $entity_base->get('admin_permission');

    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_base->id());

    if ($account->hasPermission('bypass ' . $entity_base_string . ' access')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission($entity_base_admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $operation = $route->getOption('operation');

    if ($operation == 'add') {
      return $access_control_handler->createAccess($entity_base_type->id(), $account, [], TRUE);
    }

    // If checking whether an entity of any type may be created.
    foreach ($this->entityTypeManager->getStorage($entity_base_type->id())->loadMultiple() as $entity_base_type) {
      if (($access = $access_control_handler->createAccess($entity_base_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }
}