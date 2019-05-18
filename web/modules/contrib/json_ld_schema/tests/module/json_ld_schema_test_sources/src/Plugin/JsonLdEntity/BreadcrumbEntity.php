<?php

namespace Drupal\json_ld_schema_test_sources\Plugin\JsonLdEntity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\json_ld_schema\Entity\JsonLdEntityBase;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

/**
 * A Breadcrumb entity.
 *
 * @JsonLdEntity(
 *   label = "Breadcrumb Entity",
 *   id = "breadcrumb_entity",
 * )
 */
class BreadcrumbEntity extends JsonLdEntityBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityInterface $entity, $view_mode) {
    if ($view_mode === 'full') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(EntityInterface $entity, $view_mode): Type {
    $route_match = \Drupal::routeMatch();
    $breadcrumbs = \Drupal::service('breadcrumb')->build($route_match)->getLinks();

    $items = [];
    $pos = 0;
    foreach ($breadcrumbs as $link) {
      $items[] = Schema::listItem()
        ->position(++$pos)
        ->name(strip_tags($link->toString()));
    }

    return Schema::breadcrumbList()
      ->itemListElement($items);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(EntityInterface $entity, $view_mode): CacheableMetadata {
    $metadata = parent::getCacheableMetadata($entity, $view_mode);
    $metadata->addCacheTags($entity->getCacheTags());
    return $metadata;
  }

}
