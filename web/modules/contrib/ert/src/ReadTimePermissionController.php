<?php

/**
 * @file
 * Contains \Drupal\ert\ReadTimePermissionController.
 */

namespace Drupal\ert;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the ert module.
 */
class ReadTimePermissionController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ReadTimePermissionController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of ert permissions
   *
   * @return array
   */
  public function readTimePermissions() {
    $permissions = [];

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Create a permission for each entity type to manage the entity read time
      if ($entity_type->hasLinkTemplate('read-time')) {
        $permissions['administer ' . $entity_type_id . ' read time'] = [
          'title' => $this->t('%entity_label: Administer entity read time', ['%entity_label' => $entity_type->getLabel()]),
          'restrict access' => TRUE,
        ];
      }
    }

    return $permissions;
  }

}
