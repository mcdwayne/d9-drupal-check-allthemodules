<?php

namespace Drupal\simple_modal_entity_form\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a default implementation for local action plugins.
 */
class LocalActionWithModal extends LocalActionDefault {

  /**
   * {@inheritdoc}
   *
   * @todo add dependency injection.
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options['attributes']['class'][] = 'use-ajax';
    $options['query']['destination'] = \Drupal::request()->getRequestUri();
    return $options;
  }


}
