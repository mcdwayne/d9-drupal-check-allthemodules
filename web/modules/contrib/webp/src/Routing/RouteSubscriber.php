<?php

namespace Drupal\webp\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /* @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    if ($this->moduleHandler->moduleExists('imageapi_optimize')) {
      return;
    }

    if ($route = $collection->get('image.style_public')) {
      $route->setDefault('_controller', 'Drupal\webp\Controller\ImageStyleDownloadController::deliver');
    }
  }

}
