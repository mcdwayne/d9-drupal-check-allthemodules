<?php

namespace Drupal\access_by_entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accessByEntityStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   *
   *   \Drupal\access_by_entity\AccessByEntityStorageInterface
   *   $access_by_entity_storage Current user.
   */
  public function __construct(AccountInterface $current_user,
    AccessByEntityStorageInterface $access_by_entity_storage,
    EntityTypeManagerInterface $entity_type_manager,
     $config
  ) {
    $this->currentUser = $current_user;
    $this->accessByEntityStorage = $access_by_entity_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('access_by_entity.access_storage'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Adds Access entities links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    $list_types_enabled = $this->config->get('access_by_entity.settings')->get('access_by_entity.entity_types');
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityType
        && isset($list_types_enabled[$entity_type_id])
        && $list_types_enabled[$entity_type_id] !== 0
      ) {
        $entity_type->setLinkTemplate(
          'access-entity', "/$entity_type_id/{{$entity_type_id}}/access"
        );
      }
    }
  }

  /**
   * Adds devel operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($this->currentUser->hasPermission('administer access by entity')) {
      if ($entity->hasLinkTemplate('access-entity')) {
        $operations['access-entity'] = [
          'title' => $this->t('Restrict access'),
          'weight' => 100,
          'url' => $entity->toUrl('access-entity'),
        ];
      }
    }
    return $operations;
  }

  /**
   * Alter devel operations on entity that supports it.
   *
   * @param array $operations
   *   The entity on which to define an operation.
   *
   *   \Drupal\Core\Entity\EntityInterface $entity
   *   An array of operation definitions.
   *
   * @see hook_entity_entity_operation_alter()
   */
  public function entityOperationAlter(array &$operations, EntityInterface $entity) {
    foreach ($operations as $key => $operation) {
      if (!$this->accessByEntityStorage->isAccessAllowed($entity->id(), $entity->getEntityTypeId(), $key)) {
        unset($operations[$key]);
      }
    }
  }

  /**
   * Alter devel operations on entity that supports it.
   *
   * @param array $data
   *   The entity on which to define an operation.
   *
   *   array $route_name
   *   An array of operation definitions.
   *
   * @see hook_entity_entity_operation_alter()
   */
  public function entityLocalTasksAlter(&$data, $route_name) {
    $routes = [];
    foreach (
      $this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type
    ) {
      $routes["entity.$entity_type_id.canonical"] = 'view';
      $routes["entity.$entity_type_id.edit_form"] = 'edit';
      $routes["entity.$entity_type_id.delete_form"] = 'delete';
    }
    if (isset($data['tabs'][0])) {
      foreach ($data['tabs'][0] as $href => $tab_data) {
        if (isset($routes[$href])) {
          $link_params = $tab_data['#link']['url']->getRouteParameters();
          if (!empty($link_params)) {
            $entity_id = array_values($link_params);
            $entity_type = array_keys($link_params);
            if (!empty($entity_id) && !empty($entity_type)) {
              if (!$this->accessByEntityStorage->isAccessAllowed(
                $entity_id[0], $entity_type[0], $routes[$href]
              )
              ) {
                unset($data['tabs'][0][$href]);
              }
            }
          }
        }
      }
    }

  }

}
