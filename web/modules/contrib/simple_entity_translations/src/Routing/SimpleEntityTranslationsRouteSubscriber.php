<?php

namespace Drupal\simple_entity_translations\Routing;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity translation routes.
 */
class SimpleEntityTranslationsRouteSubscriber extends RouteSubscriberBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   */
  public function __construct(ContentTranslationManagerInterface $contentTranslationManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->contentTranslationManager = $contentTranslationManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entityType) {
      $this->addRoutes($entityType, $collection, 'entity');

      if ($bundleEntityTypeId = $entityType->getBundleEntityType()) {
        $bundleEntityType = $this->entityTypeManager->getDefinition($bundleEntityTypeId);
        $this->addRoutes($bundleEntityType, $collection, 'list');
      }
    }
  }

  /**
   * Helper method to add routes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   * @param string $type
   *   Type of routes (list or entity).
   */
  protected function addRoutes(EntityTypeInterface $entityType, RouteCollection $collection, $type) {
    $entityTypeId = $entityType->id();

    $linkTemplate = $entityType->getLinkTemplate('canonical');
    if (!$linkTemplate) {
      $linkTemplate = $entityType->getLinkTemplate('overview-form');
      if (!$linkTemplate) {
        $linkTemplate = $entityType->getLinkTemplate('edit-form');
      }
    }
    if (strpos($linkTemplate, '/') !== FALSE) {
      $basePath = '/' . $linkTemplate;
    }
    else {
      if (!$entityRoute = $collection->get("entity.$entityTypeId.canonical")) {
        return;
      }
      $basePath = $entityRoute->getPath();
    }

    // Inherit admin route status from edit route, if exists.
    $isAdmin = FALSE;
    $routeName = "entity.$entityTypeId.edit_form";
    if ($edit_route = $collection->get($routeName)) {
      $isAdmin = (bool) $edit_route->getOption('_admin_route');
    }

    $path = $basePath . '/' . $type . '_translations';
    $loadLatestRevision = ContentTranslationManager::isPendingRevisionSupportEnabled($entityTypeId);

    $route = new Route(
      $path,
      [
        '_controller' => '\Drupal\simple_entity_translations\TranslationController::get' . ucfirst($type) . 'Form',
        '_title' => 'Edit',
        'entity_type_id' => $entityTypeId,
      ],
      [
        '_custom_access' => '\Drupal\simple_entity_translations\TranslationController::access',
      ],
      [
        'parameters' => [
          $entityTypeId => [
            'type' => 'entity:' . $entityTypeId,
            'load_latest_revision' => $loadLatestRevision,
          ],
        ],
        '_admin_route' => $isAdmin,
      ]
    );
    $collection->add("entity.$entityTypeId.simple_entity_translations_{$type}_edit", $route);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
    return $events;
  }

}
