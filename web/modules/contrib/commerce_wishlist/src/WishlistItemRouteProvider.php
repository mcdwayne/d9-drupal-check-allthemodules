<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Access\WishlistItemDetailsAccessCheck;
use Drupal\commerce_wishlist\Controller\WishlistItemController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the wishlist item entity.
 */
class WishlistItemRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($details_form_route = $this->getDetailsFormRoute($entity_type)) {
      $collection->add('entity.commerce_wishlist_item.details_form', $details_form_route);
    }

    return $collection;
  }

  /**
   * Gets the details-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDetailsFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('details-form'));
    $route
      ->addDefaults([
        '_controller' => WishlistItemController::class . '::detailsForm',
        '_title' => 'Edit details',
      ])
      ->setRequirement('_custom_access', WishlistItemDetailsAccessCheck::class . '::checkAccess')
      ->setOption('parameters', [
        'commerce_wishlist_item' => ['type' => 'entity:commerce_wishlist_item'],
      ]);

    return $route;
  }

}
