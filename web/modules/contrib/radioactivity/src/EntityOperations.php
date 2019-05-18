<?php

namespace Drupal\radioactivity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 *
 * @package Drupal\radioactivity
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $bundleInfo;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->moduleHandler = $moduleHandler;
    $this->bundleInfo = $bundleInfo;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Determines if the entity is moderated by the Content Moderation module.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   *  TRUE if the entity leverages content moderation, FALSE otherwise.
   */
  private function entityLeveragesContentModeration(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    if ($entity->getEntityType()->hasHandlerClass('moderation')) {
      $bundles = $this->bundleInfo->getBundleInfo($entity->getEntityType()->id());
      return isset($bundles[$entity->bundle()]['workflow']);
    }

    return FALSE;
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @see hook_entity_presave()
   */
  public function entityPresave(EntityInterface $entity) {
    // Check if the entity is being updated by the radioactivity processor.
    if (!isset($entity->radioactivityUpdate)) {
      return;
    }

    if (!$this->moduleHandler->moduleExists('content_moderation')
      || !$this->entityLeveragesContentModeration($entity)) {
      // Entity is not using content moderation, so we don't have to implement our work around
      return;
    }

    // Prevent the Content Moderation module from creating a new revision.
    $entity->setNewRevision(FALSE);
  }

}
