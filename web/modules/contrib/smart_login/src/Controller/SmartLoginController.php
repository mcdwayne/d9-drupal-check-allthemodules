<?php

/**
 * @file
 * Definition of \Drupal\smart_login\Controller\SmartLoginController.
 */

namespace Drupal\smart_login\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Smart Login.
 */
class SmartLoginController extends ControllerBase {
  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The HTTP kernel to handle forms returning response objects.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernel
   */
  protected $httpKernel;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(RouteProviderInterface $routeProvider, RouteMatchInterface $routeMatch, FormBuilderInterface $formBuilder, AliasManagerInterface $aliasManager = null, HttpKernelInterface $httpKernel = null) {
    $this->routeProvider = $routeProvider;
    $this->routeMatch = $routeMatch;
    $this->formBuilder = $formBuilder;
    $this->aliasManager = $aliasManager;
    $this->httpKernel = $httpKernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('path.alias_manager'),
      $container->get('http_kernel')
    );
  }

  /**
   * Error 403 Page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function error403Page() {
    global $base_path;
    $request = \Drupal::request();
    $destination = $request->get('destination', null);
    $interalDestination = null;
    $request->query->set('destination', null);

    if (!UrlHelper::isExternal($destination)) {
      $interalDestination = str_replace($base_path, '/', '/' . ltrim($destination, '/'));
      $destination = null;
    }

    if (\Drupal::currentUser()->isAuthenticated()) {
      // Access Restricted.
      $page403 = \Drupal::config('smart_login.settings')->get('page.403');
      $path = '/' . ltrim($this->aliasManager->getPathByAlias($page403), '/');
      $current_path = \Drupal::service('path.current')->getPath();
      $return = null;

      if ('/' != $path && $path != $current_path && $path != $destination) {
        if ($request->getMethod() === 'POST') {
          $subrequest = Request::create($request->getBaseUrl() . $path, 'POST', ['destination' => $destination, '_exception_statuscode' => 403] + $request->request->all(), $request->cookies->all(), [], $request->server->all());
        }
        else {
          $subrequest = Request::create($request->getBaseUrl() . $path, 'GET', ['destination' => $destination, '_exception_statuscode' => 403], $request->cookies->all(), [], $request->server->all());
        }

        $response = $this->httpKernel->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
        return $response;
      }

      $content = [
        '#markup' => $this->t('You are not authorized to access this page.'),
        '#title' => $this->t('Access denied'),
      ];

      return $content;
    }

    $pathIsAdmin = false;

    if (!empty($interalDestination)) {
      try {
        $url = Url::fromUri('internal:' . $interalDestination);
        $route = \Drupal::service('router.route_provider')->getRouteByName($url->getRouteName());
        $pathIsAdmin = \Drupal::service('router.admin_context')->isAdminRoute($route);
      }
      catch (\Exception $e) {
        // Empty.
      }
    }

    // Redirect to login page.
    if ($pathIsAdmin) {
      $url = 'smart_login.admin_login';
    }
    else {
      $url = 'user.login';
    }

    $url = \Drupal::url($url, ['query' => ['destination' => $destination]], ['absolute' => TRUE]);

    return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
  }

  /**
   * Login Page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function loginPage() {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $url = \Drupal::configFactory()->get('smart_login.settings')->get('admin.loggedin_redirect');

      if (empty($url)) {
        $url = 'system.admin';
      } else {
        try {
          $url = Url::fromUri('internal:/' . ltrim($url, '/'));
          $url = $url->getRouteName();
        }
        catch (\Exception $e) {
          $url = 'system.admin';
        }
      }

      $url = \Drupal::url($url, [], ['absolute' => TRUE]);

      return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }

    $build = [];
    $build['#title'] = $this->t('Log in to @site', ['@site' => \Drupal::config('system.site')->get('name')]);
    $build[] = \Drupal::formBuilder()->getForm('\Drupal\user\Form\UserLoginForm');

    return $build;
  }

  /**
   * Forgot Passowrd Page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A Symfony direct response object.
   */
  public function passwordPage() {
    $build = [];
    $build[] = \Drupal::formBuilder()->getForm('\Drupal\user\Form\UserPasswordForm');

    return $build;
  }
}
