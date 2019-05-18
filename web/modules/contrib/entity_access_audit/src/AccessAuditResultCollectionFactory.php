<?php

namespace Drupal\entity_access_audit;

use function BenTools\CartesianProduct\cartesian_product;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_access_audit\Dimensions\BundleDimension;
use Drupal\entity_access_audit\Dimensions\EntityOwnerDimension;
use Drupal\entity_access_audit\Dimensions\EntityTypeDimension;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\entity_access_audit\Dimensions\RoleDimension;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A factory to create audit result collections.
 */
class AccessAuditResultCollectionFactory implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create an instance of AccessAuditResultCollectionFactory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Generate an access audit result collection.
   *
   * @param \Drupal\entity_access_audit\AccessDimensionInterface[] $dimensions
   *   Dimensions to generate the collection for.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResultCollection
   *   A collection of access audits.
   */
  protected function generateCollection($dimensions) {
    $collection = new AccessAuditResultCollection($dimensions);
    // Find the cartesian product of all dimensions given and create an access
    // result for each of them.
    foreach (cartesian_product($dimensions) as $combination) {
      $audit_result = $this->getAuditResult($combination);
      $collection->addAuditResult($audit_result);
    }
    return $collection;
  }

  /**
   * Create a collection with all possible dimensions for an entity type.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResultCollection
   *   A collection.
   */
  public function createCollectionAllDimensions(EntityTypeInterface $entityType) {
    $dimensions = [];

    // A required dimension to the assign the entity type.
    $dimensions[EntityTypeDimension::class] = [new EntityTypeDimension($entityType)];

    // All roles can be used as a dimension for all entity types.
    foreach (Role::loadMultiple() as $role) {
      if ($role->id() === RoleInterface::AUTHENTICATED_ID) {
        continue;
      }
      $dimensions[RoleDimension::class][] = new RoleDimension($role);
    }

    // If the entity type is bundleable, add all bundles as a dimension.
    if ($bundle_entity_type = $entityType->getBundleEntityType()) {
      foreach ($this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple() as $bundle_entity_type) {
        $dimensions[BundleDimension::class][] = new BundleDimension($bundle_entity_type);
      }
    }

    // Add a dimension for the standard CRUD operations.
    foreach (['create', 'view', 'update', 'delete'] as $operation) {
      $dimensions[OperationDimension::class][] = new OperationDimension($operation);
    }

    // Entity owners are commonly used for access results, if the entity class
    // supports it, add this as a dimension too.
    if ($entityType->entityClassImplements(EntityOwnerInterface::class)) {
      $dimensions[EntityOwnerDimension::class][] = new EntityOwnerDimension(TRUE);
      $dimensions[EntityOwnerDimension::class][] = new EntityOwnerDimension(FALSE);
    }

    return $this->generateCollection($dimensions);
  }

  /**
   * Create a collection with just the anonymous user CRUD operations.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResultCollection
   *   A collection.
   */
  public function createCollectionAnonymousUserCrud(EntityTypeInterface $entityType) {
    $dimensions[EntityTypeDimension::class] = [new EntityTypeDimension($entityType)];
    $dimensions[RoleDimension::class] = [new RoleDimension(Role::load('anonymous'))];

    // Add a dimension for the standard CRUD operations.
    foreach (['create', 'view', 'update', 'delete'] as $operation) {
      $dimensions[OperationDimension::class][] = new OperationDimension($operation);
    }

    return $this->generateCollection($dimensions);
  }

  /**
   * Get the access result for a combination of dimensions.
   *
   * @param \Drupal\entity_access_audit\AccessDimensionInterface[] $dimensions
   *   The dimension combination.
   *
   * @return \Drupal\entity_access_audit\AccessAuditResult;
   *   The resulting access audit result.
   */
  protected function getAuditResult($dimensions) {
    /** @var EntityTypeInterface $entity_type */
    $entity_type = $dimensions[EntityTypeDimension::class]->getEntityType();
    $entity_storage = $this->entityTypeManager->getStorage($entity_type->id());

    $initial_entity_values = [];
    if (isset($dimensions[BundleDimension::class])) {
      $initial_entity_values[$entity_type->getKey('bundle')] = $dimensions[BundleDimension::class]->getBundleId();
    }
    elseif ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      // If we aren't using a bundle dimension, but the entity type is
      // bundleable, add a default.
      $sample_bundle_entity_types = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
      $sample_bundle_entity = array_shift($sample_bundle_entity_types);
      $initial_entity_values[$entity_type->getKey('bundle')] = $sample_bundle_entity ? $sample_bundle_entity->id() : 'default';
    }

    // Merge in any defaults required to create a valid entity.
    $defaults = $this->getEntityTypeRequiredDefaults();
    if (isset($defaults[$entity_type->id()])) {
      $initial_entity_values = array_merge($initial_entity_values, $defaults[$entity_type->id()]);
    }

    $entity = $entity_storage->create($initial_entity_values);

    // Create a user account for the access check. The anonymous and
    // authenticated roles cannot be added manually.
    $role_id = $dimensions[RoleDimension::class]->getRoleId();
    if (RoleInterface::ANONYMOUS_ID === $role_id) {
      $account = User::load(0);
    }
    else {
      // Create a fake user with an ID that can be assigned to an entity as the
      // owner. Also make the ID unique so that entity access control handlers
      // do not cache access checks based on the account ID.
      static $counter;
      $counter = $counter ? $counter + 1 : 1;
      $account = User::create([
        'uid' => 9999999999999 + $counter,
      ]);
      $account->addRole($role_id);
    }

    // If the entity owner dimension is set, ensure the entity is owned by the
    // user.
    if (isset($dimensions[EntityOwnerDimension::class]) && $entity instanceof EntityOwnerInterface && $dimensions[EntityOwnerDimension::class]->isEntityOwner()) {
      $entity->setOwner($account);
    }

    $access = $entity->access($dimensions[OperationDimension::class]->getOperation(), $account, TRUE);
    return new AccessAuditResult($access, $dimensions);
  }

  /**
   * Some entity types require
   */
  protected function getEntityTypeRequiredDefaults() {
    return [
      'block' => [
        'plugin' => 'system_powered_by_block',
        'id' => 'foo',
      ],
      'editor' => [
        'editor' => 'ckeditor',
        'format' => 'plain_text',
      ],
      'file' => [
        // File errors out without a UID. If we are not testing with the entity
        // owner dimension, simply set it to an owner which will never align with
        // the test user.
        'uid' => 1234,
      ],
      'comment' => [
        'entity_id' => [
          'entity' => Node::create(['type' => 'foo']),
        ],
      ],
      'search_page' => [
        'plugin' => 'node_search',
      ],
      'workflow' => [
        'type' => 'content_moderation',
      ],
      'language_content_settings' => [
        'target_entity_type_id' => 'node',
        'target_bundle' => 'page',
      ],
    ];
  }

}
