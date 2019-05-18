<?php

namespace Drupal\content_locker\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\content_locker\Ajax\ContentLockerUpdateEntityCommand;

/**
 * Default content renderer for ajax requests.
 */
class ContentLockerRenderer implements MainContentRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    if (isset($content['#entity_type']) && !empty($content['#' . $content['#entity_type']])) {
      $entity = $content['#' . $content['#entity_type']];
      $plugin = $this->getRequestType($request);
      $response->addCommand(new ContentLockerUpdateEntityCommand($entity, $plugin, $content));
    }

    return $response;
  }

  /**
   * Get request content locker type.
   */
  public function getRequestType($request) {
    $options = $request->request->get('dialogOptions', []);
    return isset($options['type']) ? $options['type'] : FALSE;
  }

}
