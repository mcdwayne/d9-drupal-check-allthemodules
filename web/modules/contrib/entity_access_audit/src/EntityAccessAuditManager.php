<?php

namespace Drupal\entity_access_audit;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to collect access metadata for entity types.
 */
class EntityAccessAuditManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Entity types to skip based on being difficult to access check.
   *
   * @var array
   */
  protected $skipEntityTypes = [
    'field_config',
    'field_storage_config',
    'entity_form_display',
    'entity_view_display',
    'base_field_override',
    'page_variant',
  ];

  /**
   * Create instance of EntityAccessAuditManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ClassResolverInterface $classResolver) {
    $this->entityTypeManager = $entityTypeManager;
    $this->classResolver = $classResolver;
  }

  /**
   * Get entity types which should be audited.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   Applicable entity definitions.
   */
  public function getApplicableEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function(EntityTypeInterface $entity_type) {
      return !in_array($entity_type->id(), $this->skipEntityTypes);
    });
  }

  /**
   * Get all the access results for each role.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResultCollection
   *   A result collection.
   */
  public function getAuditForEntityType($entityTypeId) {
    $definition = $this->entityTypeManager->getDefinition($entityTypeId);
    $factory = $this->classResolver->getInstanceFromDefinition(AccessAuditResultCollectionFactory::class);
    return $factory->createCollectionAllDimensions($definition);
  }

  /**
   * Get all the access results for each role.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResultCollection
   *   A result collection.
   */
  public function getOverviewAuditForEntityType($entityTypeId) {
    $definition = $this->entityTypeManager->getDefinition($entityTypeId);
    $factory = $this->classResolver->getInstanceFromDefinition(AccessAuditResultCollectionFactory::class);
    return $factory->createCollectionAnonymousUserCrud($definition);
  }

}
