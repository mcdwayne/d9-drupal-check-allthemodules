<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdEntity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\json_ld_schema\Entity\JsonLdEntityBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * A test entity.
 *
 * @JsonLdEntity(
 *   label = "Node Full Test Entity",
 *   id = "node_full_test",
 * )
 */
class NodeFullTestEntity extends JsonLdEntityBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityInterface $entity, $view_mode) {
    return $entity->getEntityTypeId() === 'node' && $view_mode === 'full';
  }

  /**
   * {@inheritdoc}
   */
  public function getData(EntityInterface $entity, $view_mode): Type {
    // As a test, add a cache context to the node view mode to ensure we can
    // vary by query string.
    $rating = \Drupal::request()->query->get('star_rating');
    $state = \Drupal::state();
    return Schema::brewery()
      ->name($entity->label())
      ->aggregateRating([
        Schema::aggregateRating()
          ->ratingValue($rating === 'high' ? $state->get('json_ld_entity_test_rating_high', 5) : $state->get('json_ld_entity_test_rating_low', 1)),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(EntityInterface $entity, $view_mode): CacheableMetadata {
    $metadata = parent::getCacheableMetadata($entity, $view_mode);
    $metadata->addCacheContexts(['url.query_args']);
    return $metadata;
  }

}
