<?php

namespace Drupal\entity_grants\Grants;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base operations for entity grants.
 */
class EntityGrantsOperationsProviderBase implements EntityGrantsOperationsProviderInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntityPermissionProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, ModuleHandlerInterface $module_handler) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityTypeInterface $entity_type) {
    $operations = $this->getDefaultOperations($entity_type);
    $operations += $this->moduleHandler->invokeAll('entity_grants_operations', [$entity_type]);
    $this->moduleHandler->alter('entity_grants_operations', $operations, $entity_type);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return array The array structure is identical to the return value of self::getOperations().
   */
  protected function getDefaultOperations(EntityTypeInterface $entity_type) {
    $operations = [];

    if ($entity_type->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 0,
      ];
    }
    if ($entity_type->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
      ];
    }
    if ($entity_type->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
      ];
    }

    return $operations;
  }

}
