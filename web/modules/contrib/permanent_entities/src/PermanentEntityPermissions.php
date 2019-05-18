<?php

namespace Drupal\permanent_entities;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\permanent_entities\Entity\PermanentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the permanent_entity module.
 *
 * @see permanent_entities.permissions.yml
 */
class PermanentEntityPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a TaxonomyPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Get permanent_entity permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach (PermanentEntityType::loadMultiple() as $permanent_entity_type) {
      $permissions += $this->buildPermissions($permanent_entity_type);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of permanent_entity permissions for a given type.
   *
   * @param \Drupal\permanent_entities\Entity\PermanentEntityType $permanent_entity_type
   *   The permanent_entity_type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(PermanentEntityType $permanent_entity_type) {
    $id = $permanent_entity_type->id();
    $args = ['%permanent_entity_type' => $permanent_entity_type->label()];

    return [
      "edit permanent entities of type $id" => [
        'title' => $this->t('%permanent_entity_type: Edit permanent entities', $args),
      ],
    ];
  }

}
