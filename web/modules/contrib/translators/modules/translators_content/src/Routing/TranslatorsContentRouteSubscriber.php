<?php

namespace Drupal\translators_content\Routing;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\translators_content\Controller\TranslatorsContentController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class TranslatorsContentRouteSubscriber.
 *
 * Subscriber for entity translation routes.
 *
 * @package Drupal\translators_content\Routing
 */
class TranslatorsContentRouteSubscriber extends RouteSubscriberBase {
  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   The content translation manager.
   */
  public function __construct(ContentTranslationManagerInterface $manager) {
    $this->contentTranslationManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $route_name = "entity.$entity_type_id.content_translation_overview";

      if ($this->contentTranslationManager->isEnabled($entity_type_id)) {
        if (!empty($route = $collection->get($route_name))) {
          $route->setDefault('_controller', TranslatorsContentController::class . '::overview');

          $more_lang_route = clone $route;
          $more_lang_route->setPath($route->getPath() . '/' . 'more/{method}');
          $more_lang_route->setDefault('_controller', TranslatorsContentController::class . '::getMoreLanguages');
          $more_lang_route->setDefault('method', 'noajax');
          $more_lang_route->setRequirements(['method' => 'noajax|ajax', '_access' => 'TRUE']);
          $more_lang_route_name = implode('.', [$route_name, 'more']);

          $collection->add($more_lang_route_name, $more_lang_route);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210 * 10];
    return $events;
  }

}
