<?php

/**
 * @file
 * Contains \Drupal\overlay\EventSubscriber\OverlaySubscriber.
 */

namespace Drupal\static_page\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * static page subscriber for controller requests.
 */
class StaticPageSubscriber implements EventSubscriberInterface {

  /**
   * Performs check on the beginning of a request.
   */
  public function onRequest(GetResponseEvent $event) {
    //$current_path = \Drupal::service('path.current')->getPath();
    //$url_object = \Drupal::service('path.validator')->getUrlIfValid($form_state->getValue($current_path));
    //$route_name = $url_object->getRouteName();
	$route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == 'entity.node.canonical') {
	  $node = \Drupal::routeMatch()->getParameter('node');
    }
    elseif ($route_name == 'entity.node.revision') {
      $vid = \Drupal::routeMatch()->getParameter('node_revision');
      $node = node_revision_load($vid);
    }
    if (!empty($node)) {
	  $type = $node->getType();
      $config = \Drupal::config('static_page.fields');
      $static_fields = $config->get('fields');
      if (!empty($static_fields[$type])) {
        $static_page = $node->get($static_fields[$type])->value;
        $response = new Response($static_page);
        $event->setResponse($response);
	  }
	}

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequest');
    //$events[KernelEvents::RESPONSE][] = array('onResponse');

    return $events;
  }
}
