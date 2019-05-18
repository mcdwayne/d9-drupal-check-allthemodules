<?php

namespace Drupal\contacts_events;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Event entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EventHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($booking_route = $this->getBookingRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.book", $booking_route);
    }

    if ($booking_process_route = $this->getBookingProcessRoute($entity_type)) {
      $collection->add("entity.commerce_order.booking_process", $booking_process_route);
    }

    return $collection;
  }

  /**
   * Gets the book now route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getBookingRoute(EntityTypeInterface $entity_type) {
    $route = new Route("/event/{contacts_event}/book");
    $route
      ->setDefaults([
        '_controller' => 'Drupal\contacts_events\Controller\EventController::book',
        '_title' => "Book Now",
      ])
      // Access controls are handled in the controller.
      ->setRequirement('_access', 'TRUE');
    // @todo: As we create entities (albeit empty), let's protect from CSRF.
    // Currently not done due to token changes when logging in.
    return $route;
  }

  /**
   * Gets booking process route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getBookingProcessRoute(EntityTypeInterface $entity_type) {
    $route = new Route("/booking/{commerce_order}");
    $route
      ->setDefaults([
        '_entity_form' => 'commerce_order.booking_process',
        '_title' => "Booking",
      ])
      ->setRequirement('_permission', 'can book for events');

    return $route;
  }

}
