<?php

namespace Drupal\efs;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for Extra field entities.
 */
class ExtraFieldHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Provide your custom entity routes here.
    foreach ($collection->getIterator() as $key => $value) {
      if ($key == 'entity.extra_field.delete_form') {
        continue;
      }
      $req = $value->getRequirements();
      $req['_access'] = 'FALSE';
      $value->setRequirements($req);
    }
    return $collection;
  }

}
