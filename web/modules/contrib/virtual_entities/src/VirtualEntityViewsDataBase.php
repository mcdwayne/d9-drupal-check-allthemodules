<?php

namespace Drupal\virtual_entities;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\views\EntityViewsDataInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides generic views integration for entities.
 */
class VirtualEntityViewsDataBase implements EntityHandlerInterface, EntityViewsDataInterface {

  use StringTranslationTrait;

  /**
   * Constructs an EntityViewsData object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to provide views integration for.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage_controller
   *   The storage handler used for this entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ContentEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager) {
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->storage = $storage_controller;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {}

  /**
   * {@inheritdoc}
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {}

}
