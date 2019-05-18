<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity manager service.
 */
class GenericConfigManager implements GenericConfigManagerInterface {

  /**
   * The entity type.
   */
  protected $entityType;

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
  public function getOptions() {
    $entities = $this->getAll();
    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }
    natsort($options);

    return $options;
  }

}
