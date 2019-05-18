<?php

namespace Drupal\entity_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for Entity Tab admin pages.
 */
class EntityUiPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityUiPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
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
   * Returns an array of Entity UI admin permissions.
   *
   * @return array
   */
  public function entityUiAdminPermissions() {
    $permissions = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->entityTypeManager->hasHandler($entity_type_id, 'entity_ui_admin')) {
        // Create a permission for each entity type to manage the Entity Tabs.
        $permissions['administer ' . $entity_type_id . ' entity tabs'] = [
          'title' => $this->t('%entity_label: Administer entity tabs', [
            '%entity_label' => $entity_type->getLabel(),
          ]),
          'restrict access' => TRUE,
        ];
      }
    }

    return $permissions;
  }

  /**
   * Returns an array of Entity UI tab permissions.
   *
   * @return array
   */
  public function entityUiTabPermissions() {
    $permissions = [];

    // Load all entity tabs.
    // TODO: consider performance; load be each entity type so we don't have
    // them all in memory at once?
    $storage = $this->entityTypeManager->getStorage('entity_tab');
    $tabs = $storage->loadMultiple();

    foreach ($tabs as $tab) {
      $permissions += $tab->getPermissions();
    }

    return $permissions;
  }

}
