<?php

namespace Drupal\ajax_assets_plus\Controller;

use Drupal\ajax_assets_plus\Ajax\ViewAjaxAssetsPlusResponse;
use Drupal\ajax_assets_plus\EventSubscriber\AjaxAssetsPlusResponseSubscriber;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\views\Ajax\ScrollTopCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\views\Controller\ViewAjaxController;

/**
 * Defines a controller to load a view via AJAX.
 */
class ViewAjaxAssetsPlusController extends ViewAjaxController {

  /**
   * Loads and renders a view via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\ajax_assets_plus\Ajax\ViewAjaxAssetsPlusResponse
   *   The view response as ajax response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the view was not found.
   */
  public function ajaxView(Request $request) {
    $name = $request->get('view_name');
    $display_id = $request->get('view_display_id');
    if (isset($name) && isset($display_id)) {
      $args = $request->get('view_args');
      $args = isset($args) && $args !== '' ? explode('/', $args) : [];

      // Arguments can be empty, make sure they are passed on as NULL so that
      // argument validation is not triggered.
      $args = array_map(function ($arg) {
        return ($arg == '' ? NULL : $arg);
      }, $args);

      $path = $request->get('view_path');
      $dom_id = $request->get('view_dom_id');
      $dom_id = isset($dom_id) ? preg_replace('/[^a-zA-Z0-9_-]+/', '-', $dom_id) : NULL;
      $pager_element = $request->get('pager_element');
      $pager_element = isset($pager_element) ? intval($pager_element) : NULL;

      $response = new ViewAjaxAssetsPlusResponse();

      // Remove all of this stuff from the query of the request so it doesn't
      // end up in pagers and tablesort URLs.
      $excluded = [
        'view_name',
        'view_display_id',
        'view_args',
        'view_path',
        'view_dom_id',
        'pager_element',
        'view_base_path',
        AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER,
        AjaxAssetsPlusResponseSubscriber::AJAX_REQUEST_PARAMETER,
      ];
      foreach ($excluded as $key) {
        $request->query->remove($key);
        $request->request->remove($key);
      }

      // Load the view.
      if (!$entity = $this->storage->load($name)) {
        throw new NotFoundHttpException();
      }
      $view = $this->executableFactory->get($entity);
      if ($view && $view->access($display_id)) {
        $response->setView($view);
        // Fix the current path for paging.
        if (!empty($path)) {
          $this->currentPath->setPath('/' . $path, $request);
        }

        // Add all GET data, because many things such as tablesorts, exposed
        // filters and paging assume it.
        $request_all = $request->request->all();
        $query_all = $request->query->all();
        $request->query->replace($request_all + $query_all);

        // Overwrite the destination.
        // @see the redirect.destination service.
        $origin_destination = $path;

        // Remove some special parameters you never want to have part of the
        // destination query.
        $used_query_parameters = $request->query->all();
        // @todo Remove this parsing once these are removed from the request in
        //   https://www.drupal.org/node/2504709.
        unset($used_query_parameters[FormBuilderInterface::AJAX_FORM_REQUEST], $used_query_parameters[MainContentViewSubscriber::WRAPPER_FORMAT], $used_query_parameters['ajax_page_state']);

        $query = UrlHelper::buildQuery($used_query_parameters);
        if ($query != '') {
          $origin_destination .= '?' . $query;
        }
        $this->redirectDestination->set($origin_destination);

        // Override the display's pager_element with the one actually used.
        if (isset($pager_element)) {
          $response->addCommand(new ScrollTopCommand(".js-view-dom-id-$dom_id"));
          $view->displayHandlers->get($display_id)->setOption('pager_element', $pager_element);
        }
        // Reuse the same DOM id so it matches that in drupalSettings.
        $view->dom_id = $dom_id;

        $preview = $view->buildRenderable($display_id, $args);
        $cache_metadata = CacheableMetadata::createFromRenderArray($preview);
        $response->addCacheableDependency($cache_metadata);

        $response->addCommand(new ReplaceCommand(".js-view-dom-id-$dom_id", $preview));

        return $response;
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
