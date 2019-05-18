<?php

namespace Drupal\maestro\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a local action plugin with a dynamic title.
 */
class TemplateEditorCustomAction extends LocalActionDefault {
  
  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $template = $route_match->getParameter('maestro_template');
    $parameters = ['templateMachineName' => 'new'];
    if($template) {
      $parameters = parent::getRouteParameters($route_match);
      $parameters['templateMachineName'] = $template->id;
    }
    return $parameters;
  }

}