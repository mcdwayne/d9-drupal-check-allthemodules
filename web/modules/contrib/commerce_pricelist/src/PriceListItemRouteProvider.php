<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the price list item entity.
 */
class PriceListItemRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);
    // Replace the "Add price list item" title with "Add price".
    // The t() function is used to ensure the string is picked up for
    // translation, even though _title is supposed to be untranslated.
    $route->setDefault('_title_callback', '');
    $route->setDefault('_title', t('Add price')->getUntranslatedString());

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getEditFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_pricelist' => [
        'type' => 'entity:commerce_pricelist',
      ],
    ]);
    // AdminHtmlRouteProvider sets _admin_route for all routes except this one.
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

}
