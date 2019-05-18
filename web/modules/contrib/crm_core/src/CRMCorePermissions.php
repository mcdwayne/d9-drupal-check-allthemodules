<?php

namespace Drupal\crm_core;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a class containing permission callbacks.
 */
class CRMCorePermissions {

  use StringTranslationTrait;

  /**
   * Return permission names for a given CRM Core entity type.
   *
   * @param string $entity_type
   *   Entity type string.
   *
   * @return array
   *   Permissions.
   */
  public function entityTypePermissions($entity_type) {

    $entity_info = \Drupal::EntityTypeManager()->getDefinition($entity_type);
    $labels = $this->permissionLabels($entity_info);

    $permissions = [];

    // General 'administer' permission.
    $permissions['administer ' . $entity_type . ' entities'] = [
      'title' => $this->t('Administer @entity_type', ['@entity_type' => $labels['plural']]),
      'description' => $this->t('Allows users to perform any action on @entity_type.', ['@entity_type' => $labels['plural']]),
      'restrict access' => TRUE,
    ];

    // Generic create and edit permissions.
    $permissions['create ' . $entity_type . ' entities'] = [
      'title' => $this->t('Create @entity_type of any type', ['@entity_type' => $labels['plural']]),
    ];
    if ($entity_info->hasKey('user')) {
      $permissions['edit own ' . $entity_type . ' entities'] = [
        'title' => $this->t('Edit own @entity_type of any type', ['@entity_type' => $labels['plural']]),
      ];
    }
    $permissions['edit any ' . $entity_type . ' entity'] = [
      'title' => $this->t('Edit any @entity_type of any type', ['@entity_type' => $labels['singular']]),
      'restrict access' => TRUE,
    ];
    if ($entity_info->hasKey('user')) {
      $permissions['view own ' . $entity_type . ' entities'] = [
        'title' => $this->t('View own @entity_type of any type', ['@entity_type' => $labels['plural']]),
      ];
    }
    $permissions['view any ' . $entity_type . ' entity'] = [
      'title' => $this->t('View any @entity_type of any type', ['@entity_type' => $labels['singular']]),
      'restrict access' => TRUE,
    ];

    // Per-bundle create and edit permissions.
    if ($entity_info->hasKey('bundle')) {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      foreach ($bundles as $bundle_name => $bundle_info) {
        $permissions += $this->bundlePermissions($bundle_name, $bundle_info, $entity_info);
      }

    }

    return $permissions;
  }

  /**
   * Define per-bundle permissions.
   */
  protected function bundlePermissions($bundle_name, array $bundle_info, EntityTypeInterface $entity_info) {
    $labels = $this->permissionLabels($entity_info);

    $permissions['create ' . $entity_info->id() . ' entities of bundle ' . $bundle_name] = [
      'title' => $this->t('Create %bundle @entity_type', ['@entity_type' => $labels['plural'], '%bundle' => $bundle_info['label']]),
    ];
    if ($entity_info->hasKey('user')) {
      $permissions['edit own ' . $entity_info->id() . ' entities of bundle ' . $bundle_name] = [
        'title' => $this->t('Edit own %bundle @entity_type', ['@entity_type' => $labels['plural'], '%bundle' => $bundle_info['label']]),
      ];
    }
    $permissions['edit any ' . $entity_info->id() . ' entity of bundle ' . $bundle_name] = [
      'title' => $this->t('Edit any %bundle @entity_type', ['@entity_type' => $labels['singular'], '%bundle' => $bundle_info['label']]),
      'restrict access' => TRUE,
    ];
    if ($entity_info->hasKey('user')) {
      $permissions['delete own ' . $entity_info->id() . ' entities of bundle ' . $bundle_name] = [
        'title' => $this->t('Delete own %bundle @entity_type', ['@entity_type' => $labels['plural'], '%bundle' => $bundle_info['label']]),
      ];
    }
    $permissions['delete any ' . $entity_info->id() . ' entity of bundle ' . $bundle_name] = [
      'title' => $this->t('Delete any %bundle @entity_type', ['@entity_type' => $labels['singular'], '%bundle' => $bundle_info['label']]),
      'restrict access' => TRUE,
    ];
    if ($entity_info->hasKey('user')) {
      $permissions['view own ' . $entity_info->id() . ' entities of bundle ' . $bundle_name] = [
        'title' => $this->t('View own %bundle @entity_type', ['@entity_type' => $labels['plural'], '%bundle' => $bundle_info['label']]),
      ];
    }
    $permissions['view any ' . $entity_info->id() . ' entity of bundle ' . $bundle_name] = [
      'title' => $this->t('View any %bundle @entity_type', ['@entity_type' => $labels['singular'], '%bundle' => $bundle_info['label']]),
      'restrict access' => TRUE,
    ];

    return $permissions;
  }

  /**
   * Permission labels.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   Entity info.
   *
   * @return mixed
   *   Labels.
   */
  protected function permissionLabels(EntityTypeInterface $entity_info) {
    $labels = $entity_info->get("permission_labels");

    if (!isset($labels['singular'])) {
      $labels['singular'] = $entity_info->getLabel();
    }

    if (!isset($labels['plural'])) {
      $labels['plural'] = $entity_info->getLabel();
    }

    return $labels;
  }

}
