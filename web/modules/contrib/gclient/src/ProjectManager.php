<?php

namespace Drupal\gclient;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\integro\Annotation\IntegroIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectManager implements ProjectManagerInterface {

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
    $this->entityStorage = $entity_type_manager->getStorage('gclient_google_project');
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
  public function getOptions(array $ids = NULL) {
    $entities = $this->getAll($ids);

    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    return $options;
  }

}
