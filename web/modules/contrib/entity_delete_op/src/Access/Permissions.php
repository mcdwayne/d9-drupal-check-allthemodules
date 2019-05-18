<?php

namespace Drupal\entity_delete_op\Access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates permissions for supported entity types.
 */
class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new instance of Permissions.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of permissions for supported entity types.
   *
   * @return array
   *   The permissions.
   */
  public function generate() {
    $permissions = [];

    // @todo: In the future, support bundles.
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type->get('entity_delete_op')) {
        continue;
      }

      $t_args = ['%entity_type' => $entity_type->getLabel()];

      $permissions["entity_delete_op delete own $entity_type_id entities"] = [
        'title' => $this->t('Delete own %entity_type entities', $t_args),
      ];
      $permissions["entity_delete_op delete any $entity_type_id entities"] = [
        'title' => $this->t('Delete any %entity_type entities', $t_args),
      ];
      $permissions["entity_delete_op restore own $entity_type_id entities"] = [
        'title' => $this->t('Restore own %entity_type entities', $t_args),
      ];
      $permissions["entity_delete_op restore any $entity_type_id entities"] = [
        'title' => $this->t('Restore any %entity_type entities', $t_args),
      ];
      $permissions["entity_delete_op purge own $entity_type_id entities"] = [
        'title' => $this->t('Purge own %entity_type entities', $t_args),
      ];
      $permissions["entity_delete_op purge any $entity_type_id entities"] = [
        'title' => $this->t('Purge any %entity_type entities', $t_args),
      ];
    }

    return $permissions;
  }

}