<?php

namespace Drupal\image_style_dynamic\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class ImageStyleRoutes implements EventSubscriberInterface {

  public function onRouteAlter(RouteBuildEvent $event) {
    $event->getRouteCollection()
      ->get('image.style_public')
      ->setDefault('_controller', 'Drupal\image_style_dynamic\Controller\ImageStyleController::deliverDynamicImageStyle');
    $event->getRouteCollection()
      ->get('image.style_private')
      ->setDefault('_controller', 'Drupal\image_style_dynamic\Controller\ImageStyleController::deliverDynamicImageStyle');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onRouteAlter'];
    return $events;
  }

}
