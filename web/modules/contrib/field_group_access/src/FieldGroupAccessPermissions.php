<?php

namespace Drupal\field_group_access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for field_group_access permissions.
 */
class FieldGroupAccessPermissions implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructor.
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
   * Get the permissions for an array of bundles.
   */
  private function bundlePermissions($bundle_entities, $bundle_of) {
    $permissions = [];
    foreach ($bundle_entities as $bundle_id => $bundle) {
      // @todo handle all form modes.
      $groups = field_group_info_groups($bundle_of, $bundle_id, 'form', 'default');
      if (count($groups) > 0) {
        foreach ($groups as $group_id => $group) {
          $permissions += [
            'edit ' . $bundle_of . '.' . $bundle_id . '.default.' . $group_id => [
              'title' => $this->t('Edit fields in group %group in %bundle_id', ['%group' => $group_id, '%bundle_id' => $bundle_of . '.' . $bundle_id]),
            ],
            'view ' . $bundle_of . '.' . $bundle_id . '.default.' . $group_id => [
              'title' => $this->t('View fields in group %group in %bundle_id', ['%group' => $group_id, '%bundle_id' => $bundle_of . '.' . $bundle_id]),
            ],
          ];
        }
      }
    }

    return $permissions;
  }

  /**
   * Return a list of permissions for field groups.
   */
  public function permissions() {
    $permissions = [];
    // Get a list of entity_types.
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $bundle_of = $entity_type->get('bundle_of');

      // Only interested in entity types with bundles.
      if ($bundle_of !== NULL) {
        $entity_type_storage = $this->entityManager->getStorage($entity_type_id);
        $bundle_entities = $entity_type_storage->loadMultiple();

        // Only interested in ones which actually have some bundles.
        if ($bundle_entities !== NULL) {
          $permissions += $this->bundlePermissions($bundle_entities, $bundle_of);
        }
      }
    }

    return $permissions;
  }

}
