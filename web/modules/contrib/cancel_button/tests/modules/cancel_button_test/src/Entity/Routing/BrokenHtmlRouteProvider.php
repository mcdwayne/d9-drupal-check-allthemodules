<?php

namespace Drupal\cancel_button_test\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides HTML routes for test entity types defined in this module.
 *
 * This class ensures (for testing) that the following routes are missing
 * from EntityTestBrokenCanonicalRoute and EntityTestBrokenCollectionRoute:
 * canonical, collection.
 */
class BrokenHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($canonical_route = $this->getCanonicalRoute($entity_type)) {
      $collection->remove("entity.{$entity_type_id}.canonical");
    }

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->remove("entity.{$entity_type_id}.collection");
    }

    return $collection;
  }

}
