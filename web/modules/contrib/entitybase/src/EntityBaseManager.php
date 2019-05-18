<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseManager.
 */

namespace Drupal\entity_base;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Entity manager service.
 */
abstract class EntityBaseManager implements EntityBaseManagerInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'entity_base';

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage($this->entityType);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(array $ids = NULL) {
    return $this->entityStorage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailable(AccountInterface $user) {
    $available = [];

    $entities = $this->entityStorage->loadMultiple();
    foreach ($entities as $entity) {
      $available[$entity->id()] = $entity->label();
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getByField($field_name, $field_value) {
     $ids = \Drupal::entityQuery($this->entityType)
       ->condition($field_name, $field_value)
       ->sort('id', 'ASC')
       ->execute();

    if (!empty($ids)) {
      $entities = $this->entityStorage->loadMultiple($ids);

      if (count($entities) == 1) {
        $entity = reset($entities);
        return $entity;
      }
      else {
        return $entities;
      }
    }

    return FALSE;
  }

}
