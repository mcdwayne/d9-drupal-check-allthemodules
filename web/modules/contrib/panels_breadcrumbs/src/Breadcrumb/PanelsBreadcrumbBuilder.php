<?php

namespace Drupal\panels_breadcrumbs\Breadcrumb;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Class PanelsBreadcrumbBuilder.
 */
class PanelsBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Token service.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected $token;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PanelsBreadcrumbManager constructor.
   */
  public function __construct(RouteProviderInterface $route_provider, TokenInterface $token, AccessManagerInterface $access_manager, RequestMatcherInterface $router, AccountInterface $current_user) {
    $this->routeProvider = $route_provider;
    $this->token = $token;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $page_variant = $route_match->getParameter('page_manager_page_variant');
    $variant_settings = $page_variant->get('variant_settings');

    $titles = array_filter(array_map('trim', explode("\r\n", $variant_settings['panels_breadcrumbs']['titles'])), 'strlen');
    $paths = array_filter(array_map('trim', explode("\r\n", $variant_settings['panels_breadcrumbs']['paths'])), 'strlen');

    $entities = [];
    foreach ($page_variant->getContexts() as $id => $context) {
      $entities[$id] = $context->getContextValue();
    }

    $links = [];
    if ($variant_settings['panels_breadcrumbs']['home'] == 1) {
      $home_title = $this->getTitle($variant_settings['panels_breadcrumbs']['home_text'], $entities);
      $links[] = Link::createFromRoute($home_title, '<front>');
    }

    foreach ($titles as $key => $title) {
      $title = $this->getTitle($title, $entities);
      $path = $this->token->replace($paths[$key], $entities);

      if ($this->routeProvider->getRoutesByNames([$path])) {
        $path = Url::fromRoute($path)->toString();
      }
      if ($path && $request = $this->getRequestForPath($path)) {
        $route_match = RouteMatch::createFromRequest($request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        if ($access->isAllowed()) {
          $links[] = Link::fromTextAndUrl($title, Url::fromRouteMatch($route_match));
        }
      }
      else {
        $links[] = Link::createFromRoute($title, '<nolink>');
      }
    }

    $this->addCaching($route_match, $breadcrumb);
    $breadcrumb->setLinks($links);

    return $breadcrumb;
  }

  /**
   * Check if title is not token, and filtering It for security reason.
   */
  protected function getTitle($title, array $entities) {
    if (!$this->token->scan($title)) {
      $title = $this->t(Xss::filter($title));
    }
    else {
      $title = $this->token->replace($title, $entities);
    }
    return $title;
  }

  /**
   * Get request object from path.
   */
  protected function getRequestForPath($path) {
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Attempt to match this path to provide a fully built request.
    $request->attributes->add($this->router->matchRequest($request));
    return $request;
  }

  /**
   * Add cacheable dependencies and cache contexts.
   */
  protected function addCaching(RouteMatchInterface $route_match, Breadcrumb $breadcrumb) {
    $parameters = $route_match->getParameters();
    foreach ($parameters as $key => $parameter) {
      if ($parameter instanceof CacheableDependencyInterface) {
        $breadcrumb->addCacheableDependency($parameter);
      }
    }
    $breadcrumb->addCacheContexts(['url.path']);
  }

}
