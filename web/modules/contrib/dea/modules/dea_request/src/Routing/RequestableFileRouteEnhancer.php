<?php
namespace Drupal\dea_request\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class RequestableFileRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->getPath() == '/system/files/{scheme}';
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $defaults[RequestableRouteEnhancer::ENTITY_OPERATION] = [
      'entity' => file_uri_to_object($defaults['scheme'] . '://' . $request->get('file')),
      'operation' => 'download',
    ];
    return $defaults;
  }

}
