<?php

namespace Drupal\entity_usage_integrity\EntityUsageIntegrityResource;

use Exception;

use Drupal\Core\Entity\EntityInterface;

/**
 * Entity usage relation item.
 */
final class RelationStatusItem {

  /**
   * Entity initializing integrity check.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $currentEntity;

  /**
   * Entity related to current entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $relatedEntity;

  /**
   * Describes if current entity is 'source' or 'target' of relation.
   *
   * The sentence is like: "$current_entity is $relation of $related_entity".
   * Source means, that the entity contains field, which refers to 'target'.
   *
   * @var string
   */
  protected $relationType;

  /**
   * Create RelationItem object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_entity
   *   Entity initializing integrity check.
   * @param \Drupal\Core\Entity\EntityInterface $related_entity
   *   Entity related to current entity.
   * @param string $relation_type
   *   Describes if current entity is 'source' or 'target' of relation.
   *
   * @throws \Exception
   *   If relation type is not known type.
   */
  public function __construct(EntityInterface $current_entity, EntityInterface $related_entity, $relation_type) {
    if (!assert(in_array($relation_type, ['source', 'target']))) {
      throw new Exception('Unknown entity usage relation type.');
    }

    $this->currentEntity = $current_entity;
    $this->relatedEntity = $related_entity;
    $this->relationType = $relation_type;
  }

  /**
   * Get related entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Related entity.
   */
  public function getRelatedEntity() {
    $entity = $this->relatedEntity;
    // Related entity is paragraph entity, so let's try to get
    // parent managing the paragraph as this is something that we want
    // to display in error/warning to the end user.
    while (method_exists($entity, 'getParentEntity')) {
      $entity = $entity->getParentEntity();
    }
    // Let's return original entity, even if it's paragraph,
    // if we can't find it's parent.
    return $entity ?: $this->relatedEntity;
  }

  /**
   * Get flag describing if $currentEntity is 'source' or 'target' of relation.
   *
   * @return string
   *   The 'source' if check was initialized from source entity,
   *   the 'target' otherwise.
   */
  public function getRelationType() {
    return $this->relationType;
  }

}
