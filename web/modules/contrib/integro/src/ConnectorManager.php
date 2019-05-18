<?php

namespace Drupal\integro;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity manager service.
 */
class ConnectorManager implements ConnectorManagerInterface {

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
    $this->entityStorage = $entity_type_manager->getStorage('integro_connector');
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
  public function load($id) {
    return $this->entityStorage->load($id);
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
  public function getOptions(array $ids = NULL) {
    $entities = $this->getAll($ids);

    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    return $options;
  }

}
