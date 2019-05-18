<?php

namespace Drupal\colormenu\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the colormenu entity.
 */
class MainContentController extends ControllerBase {
  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $classResolver;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The available main content renderer services, keyed per format.
   *
   * @var array
   */
  protected $mainContentRenderers;

  /**
   * URL query attribute to indicate the wrapper used to render a request.
   *
   * The wrapper format determines how the HTML is wrapped, for example in a
   * modal dialog.
   */
  const WRAPPER_FORMAT = '_wrapper_format';

  /**
   *
   */
  public function getContent() {
    $current_path = \Drupal::service('path.current')->getPath();
    $current_route_match = \Drupal::service('current_route_match');
    $t = \Drupal::service('main_content_renderer.html');
    $r = \Drupal::service('bare_html_page_renderer');
    $re = \Drupal::service('request_stack')->getCurrentRequest();
    $d = \Drupal::service('html_response.subscriber');
    $e = \Drupal::service('main_content_view_subscriber');
    $p = \Drupal::service('event_dispatcher');
    $rc = \Drupal::service('router.request_context');
    $rr = \Drupal::service('renderer');
    $http_kernel = \Drupal::service('http_kernel');
    $route_preloader = \Drupal::service('router.route_preloader');
    $bk = \Drupal::service('http_kernel.basic');
    $ed = \Drupal::service('event_dispatcher');
    $cr = \Drupal::service('controller_resolver');
    $request_context = \Drupal::service('router.request_context');
    $admin_context = \Drupal::service('router.admin_context');
    $matcher = \Drupal::service('path.matcher');

    $tt = $p->getListeners()['kernel.view'][2];

    return [];
  }

}
