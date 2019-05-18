<?php

namespace Drupal\entity_usage_integrity\EntityUsageIntegrityResource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;

/**
 * Store collection of entity usage integrity relations.
 */
final class RelationStatusCollection implements \IteratorAggregate, \Countable {

  /**
   * Storage for relation items.
   *
   * @var \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusItem[]
   */
  protected $relations = [];

  /**
   * {@inheritdoc}
   *
   * Don't use that method directly. See alternatives.
   */
  public function getIterator() {
    return new \ArrayIterator($this->relations);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->relations);
  }

  /**
   * Add new entity usage relation item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_entity
   *   Entity initializing integrity check.
   * @param \Drupal\Core\Entity\EntityInterface $related_entity
   *   Entity related to current entity.
   * @param string $relation_type
   *   Describes if current entity is 'source' or 'target' of relation.
   */
  public function add(EntityInterface $current_entity, EntityInterface $related_entity, $relation_type) {
    array_push($this->relations, new RelationStatusItem($current_entity, $related_entity, $relation_type));
  }

  /**
   * List of related entities.
   *
   * @param string $relation_type
   *   Type of relation for related entities.
   *
   * @return array
   *   A render array.
   */
  public function getRelatedEntitiesElement($relation_type) {
    $element = [];
    /** @var \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusItem[] $relation */
    foreach ($this->relations as $relation) {
      if ($relation->getRelationType() == $relation_type) {
        $entity = $relation->getRelatedEntity();
        try {
          $element[] = [
            '#type' => 'link',
            '#title' => $entity->label(),
            '#url' => $entity->toUrl(),
            '#options' => [
              'attributes' => [
                'target' => '_blank',
              ],
            ],
          ];
        }
        catch (UndefinedLinkTemplateException $e) {
          $element[] = [
            '#type' => 'markup',
            '#title' => $entity->label() . '(' . $entity->getEntityTypeId() . ':' . $entity->id() . ')',
          ];
        }
      }
    }

    return $element;
  }

}
