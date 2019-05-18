<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBasePermissions.
 */

namespace Drupal\entity_base\Permission;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class containing permission callbacks.
 */
class EntityBasePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  protected $entityTypeId = 'entity_base';
  protected $entityType;
  protected $entityTypeManager;
  protected $entityTypeLabel;
  protected $entityTypeLabelPlural;

  /**
   * Constructs an EntityBasePermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityType = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $this->entityTypeLabel = $this->entityType->get('additional')['entity_base']['names']['label']->render();
    $this->entityTypeLabelPlural = $this->entityType->get('additional')['entity_base']['names']['label_plural']->render();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of permissions.
   *
   * @return array
   */
  public function entityPermissions() {
    $permissions = array();
    $permissions['administer ' . $this->entityTypeId] = [
      'title' => $this->t('Administer ' . $this->entityTypeLabelPlural),
      'restrict access' => TRUE,
    ];
    $permissions['bypass ' . $this->entityTypeId . ' access'] = [
      'title' => $this->t('Bypass ' . $this->entityTypeLabelPlural . ' access'),
      'restrict access' => TRUE,
    ];


    if (empty($this->entityType->get('entity_keys')['bundle']) || is_null($this->entityType->get('entity_keys')['bundle'])) {
      $permissions += $this->buildPermissions();
    }

    // Build permissions for bundles.
    if ($this->entityType->get('entity_keys')['bundle'] != '') {
      $permissions['administer ' . $this->entityTypeId . ' types'] = [
        'title' => $this->t('Administer ' . $this->entityTypeLabel . ' types'),
        'restrict access' => TRUE,
      ];

      // Generate entity permissions for all entity types.
      $entity_bundle = $this->entityTypeManager->getDefinition($this->entityType->get('bundle_entity_type'));
      $entity_bundle_class = $entity_bundle->getClass();
      foreach ($entity_bundle_class::loadMultiple() as $bundle) {
        $permissions += $this->buildBundlePermissions($bundle);
      }
    }

    return $permissions;
  }

  /**
   * Builds a standard list of permissions for a given entity bundle.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions() {
    $permissions =  array();

    $permissions['create ' . $this->entityTypeId] = [
      'title' => $this->t('Create ' . $this->entityTypeLabel),
    ];
    $permissions['view own ' . $this->entityTypeId] = [
      'title' => $this->t('View own ' . $this->entityTypeLabel),
    ];
    $permissions['view any ' . $this->entityTypeId] = [
      'title' => $this->t('View any ' . $this->entityTypeLabel),
    ];
    $permissions['edit own ' . $this->entityTypeId] = [
      'title' => $this->t('Edit own ' . $this->entityTypeLabel),
    ];
    $permissions['edit any ' . $this->entityTypeId] = [
      'title' => $this->t('Edit any ' . $this->entityTypeLabel),
    ];
    $permissions['delete own ' . $this->entityTypeId] = [
      'title' => $this->t('Delete own ' . $this->entityTypeLabel),
    ];
    $permissions['delete any ' . $this->entityTypeId] = [
      'title' => $this->t('Delete any ' . $this->entityTypeLabel),
    ];

    return $permissions;
  }

  /**
   * Builds a standard list of permissions for a given entity bundle.
   *
   * @param $bundle
   *   The machine name of the entity type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildBundlePermissions($bundle) {
    $bundleId = $bundle->id();
    $bundleParams = ['%bundle' => $bundle->label()];
    $permissions =  array();

    $permissions['create ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: Create ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['view own ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: View own ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['view any ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: View any ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['edit own ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: Edit own ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['edit any ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: Edit any ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['delete own ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: Delete own ' . $this->entityTypeLabel, $bundleParams),
    ];
    $permissions['delete any ' . $bundleId . ' ' . $this->entityTypeId] = [
      'title' => $this->t('%bundle: Delete any ' . $this->entityTypeLabel, $bundleParams),
    ];

    return $permissions;
  }

}
