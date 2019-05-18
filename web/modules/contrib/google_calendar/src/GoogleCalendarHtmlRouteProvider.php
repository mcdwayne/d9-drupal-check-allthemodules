<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Test entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class GoogleCalendarHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    return $collection;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    $part = $entity_type->id() == "google_calendar" ? "calendar" : "event";
    $route = new Route("/admin/google-calendar/$part/settings");
    $route
      ->setDefaults([
        '_form' => 'Drupal\google_calendar\Form\SettingsForm',
        '_title' => "{$entity_type->getLabel()} settings",
      ])
      ->setRequirement('_permission', $entity_type->getAdminPermission())
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}
