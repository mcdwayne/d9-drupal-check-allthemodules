<?php

namespace Drupal\disable_route_normalizer\DisableRouteNormalizer;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber;

/**
 * Class DisableRouteNormalizer.
 *
 * @package Drupal\disable_route_normalizer
 */
class DisableRouteNormalizer extends RouteNormalizerRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onKernelRequestRedirect(GetResponseEvent $event) {

    if (!$this->config->get('route_normalizer_enabled') || !$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    if ($request->attributes->get('_disable_route_normalizer')) {
      return;
    }

    /** @var \Drupal\node\Entity\Node $node */
    $node = $request->attributes->get('node');
    $languages = \Drupal::languageManager()->getLanguages();
    if ($node instanceof \Drupal\node\Entity\Node && \Drupal::config('disable_route_normalizer.settings')->get('route_ignore_neutral_nodes') && (!$node->getEntityType()->isTranslatable() || !array_key_exists($node->language()->getId(), $languages))) {
      return;
    }

    parent::onKernelRequestRedirect($event);
  }
  
}
