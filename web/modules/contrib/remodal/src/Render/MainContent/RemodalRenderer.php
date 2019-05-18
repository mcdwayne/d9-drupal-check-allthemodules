<?php

namespace Drupal\remodal\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\remodal\Ajax\OpenRemodalCommand;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\MainContent\DialogRenderer;

/**
 * Default main content renderer for remodal dialog requests.
 */
class RemodalRenderer extends DialogRenderer {

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    // First render the main content, because it might provide a title.
    $content = drupal_render_root($main_content);

    // Attach the library necessary for using the OpenRemodalCommand and set
    // the attachments for this Ajax response.
    $main_content['#attached']['library'][] = 'remodal/commands';
    $response->setAttachments($main_content['#attached']);

    // If the main content doesn't provide a title, use the title resolver.
    $title = isset($main_content['#title']) ? $main_content['#title'] : $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $options = $request->request->get('dialogOptions', array());

    $response->addCommand(new OpenRemodalCommand($title, $content, $options));
    return $response;
  }

}
