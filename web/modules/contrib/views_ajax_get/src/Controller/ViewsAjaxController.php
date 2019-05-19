<?php

namespace Drupal\views_ajax_get\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\views\Controller\ViewAjaxController;
use Drupal\views_ajax_get\CacheableViewsAjaxResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller that allows for Views AJAX GET requests that can be cached.
 */
class ViewsAjaxController extends ViewAjaxController {

  /**
   * {@inheritdoc}
   */
  public function ajaxView(Request $request) {
    if ($request->getMethod() !== Request::METHOD_GET) {
      return parent::ajaxView($request);
    }
    // Add all query parameters to the post variable, because this is what
    // \Drupal\views\Controller\ViewAjaxController::ajaxView expects.
    $request->request->add($request->query->all());
    $response = parent::ajaxView($request);

    $view = $response->getView();
    if (_views_ajax_get_is_ajax_get_view($view)) {
      $cacheable_response = new CacheableViewsAjaxResponse();
      $cacheable_response->setView($view);
      $cacheable_commands = &$cacheable_response->getCommands();
      $cacheable_commands = $response->getCommands();

      $view_metadata = CacheableMetadata::createFromRenderArray($view->element);
      $metadata = $cacheable_response->getCacheableMetadata();
      $metadata->addCacheableDependency($view_metadata);

      // Don't allow attachments
      // $cacheable_response->setAttachments($response->getAttachments());
      return $cacheable_response;
    }

    return $response;
  }

}
