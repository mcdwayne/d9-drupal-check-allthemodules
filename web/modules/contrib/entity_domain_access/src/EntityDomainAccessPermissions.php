<?php

namespace Drupal\entity_domain_access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamic permissions class for Entity Domain Access.
 */
class EntityDomainAccessPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The domain entity mapper.
   *
   * @var \Drupal\entity_domain_access\EntityDomainAccessMapper
   */
  protected $mapper;

  /**
   * Creates a new class object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\entity_domain_access\EntityDomainAccessMapper $mapper
   *   The domain entity mapper.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityDomainAccessMapper $mapper) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->mapper = $mapper;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_domain_access.mapper')
    );
  }

  /**
   * Get allowed operations.
   *
   * @param string $operation
   *   Operation to get translation.
   *
   * @return array
   *   Array of operations.
   */
  public static function getAllowedOperations($operation = NULL) {
    $operations = [
      'create' => t('Create'),
      'update' => t('Update'),
      'delete' => t('Delete'),
      'view' => t('View'),
      'view unpublished' => t('View unpublished'),
    ];

    return !is_null($operation) ? $operations[$operation] : $operations;
  }

  /**
   * Get allowed operations.
   *
   * @param string $access_type
   *   Access type to get translation.
   *
   * @return array
   *   Array of access types.
   */
  public static function getAllowedAccessTypes($access_type = NULL) {
    $access_types = [
      'any' => t('any'),
      'assigned' => t('assigned'),
    ];

    return $access_type ? $access_types[$access_type] : $access_types;
  }

  /**
   * Get permission ID.
   *
   * @param string $operation
   *   Operation from allowed operations list..
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle_id
   *   Bundle ID.
   * @param string $access_type
   *   Access type: 'any' or 'assigned'.
   *
   * @return string
   *   Permission ID.
   *
   * @see \Drupal\entity_domain_access\EntityDomainAccessPermissions::getAllowedOperations()
   */
  public static function getPermissionId($operation, $entity_type_id, $bundle_id = NULL, $access_type = 'any') {
    $bundle_id = $bundle_id ? " {$bundle_id}" : '';

    return "{$operation}{$bundle_id} {$entity_type_id} on {$access_type} domain";
  }

  /**
   * Get permission title.
   *
   * @param string $operation
   *   Operation.
   * @param string $entity_type_label
   *   Entity type label.
   * @param string $bundle_label
   *   Bundle label.
   * @param string $access_type
   *   Access type: 'any' (default) or 'assigned'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Permission title.
   */
  public static function getPermissionTitle($operation, $entity_type_label, $bundle_label = NULL, $access_type = 'any') {

    $access_type_lable = static::getAllowedAccessTypes($access_type);
    $operation_label = ucfirst(static::getAllowedOperations($operation));

    $permission_title = t('%bundle_name%operation any %type_name on %access_type domains', [
      '%bundle_name' => $bundle_label ? $bundle_label . ': ' : '',
      '%operation' => $operation_label,
      '%type_name' => $entity_type_label,
      '%access_type' => $access_type_lable,
    ]);

    return $permission_title;
  }

  /**
   * Define permissions.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->mapper->getEnabledEntityTypes() as $entity_type_id => $entity_type) {
      $has_status = method_exists($entity_type->getOriginalClass(), 'isPublished');

      $entity_type_label = $entity_type->getLabel();

      foreach ($this->getAllowedOperations() as $operation => $operation_title) {

        // Ignore 'view unpublished' operation if entity has no status.
        if ($operation == 'view unpublished' && !$has_status) {
          continue;
        }

        $permissions[static::getPermissionId($operation, $entity_type_id)] = [
          'title' => static::getPermissionTitle($operation, $entity_type_label),
        ];

        $permissions[static::getPermissionId($operation, $entity_type_id, NULL, 'assigned')] = [
          'title' => static::getPermissionTitle($operation, $entity_type_label, NULL, 'assigned'),
        ];
      }

      // Generate standard entity permissions for all applicable entity bundles.
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle_id => $bundle) {
        if (!$this->mapper->isDomainAccessEntityBundle($entity_type->id(), $bundle_id)) {
          continue;
        }
        $permissions += $this->entityPermissions($entity_type, $bundle_id, $bundle['label']);
      }
    }

    return $permissions;
  }

  /**
   * Helper method to generate standard widget permission list for a given type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type object.
   * @param string $bundle_id
   *   Bundle ID.
   * @param string $bundle_label
   *   Bundle label.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function entityPermissions(EntityTypeInterface $entity_type, $bundle_id, $bundle_label) {
    $entity_type_id = $entity_type->id();
    $entity_type_label = $entity_type->getLabel();
    $has_status = method_exists($entity_type->getOriginalClass(), 'isPublished');

    $permissions = [];
    foreach ($this->getAllowedOperations() as $operation => $operation_title) {

      // Ignore 'view unpublished' operation if entity has no status.
      if ($operation == 'view unpublished' && !$has_status) {
        continue;
      }

      $permissions[static::getPermissionId($operation, $entity_type_id, $bundle_id, 'assigned')] = [
        'title' => static::getPermissionTitle($operation, $entity_type_label, $bundle_label, 'assigned'),
      ];
    }
    return $permissions;
  }

}
