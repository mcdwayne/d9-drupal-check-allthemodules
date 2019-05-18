<?php

namespace Drupal\breadcrumb_manager\Breadcrumb;

use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverManager;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Class BreadcrumbManagerBuilder.
 *
 * @package Drupal\breadcrumb_manager\Breadcrumb
 */
class BreadcrumbManagerBuilder extends PathBasedBreadcrumbBuilder {

  use StringTranslationTrait;

  /**
   * The Breadcrumb Title Resolver Manager.
   *
   * @var \Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverManager
   */
  protected $titleResolverManager;

  /**
   * An array of Breadcrumb Title resolver plugins.
   *
   * @var \Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverInterface[]
   */
  protected $titleResolvers;

  /**
   * Breadcrumb Manager configurations.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $breadcrumbConfig;

  /**
   * An array of excluded paths.
   *
   * @var array
   */
  protected $excludedPaths;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestContext $context,
    AccessManagerInterface $access_manager,
    RequestMatcherInterface $router,
    InboundPathProcessorInterface $path_processor,
    ConfigFactoryInterface $config_factory,
    TitleResolverInterface $title_resolver,
    AccountInterface $current_user,
    CurrentPathStack $current_path,
    PathMatcherInterface $path_matcher = NULL,
    BreadcrumbTitleResolverManager $title_resolver_manager
  ) {
    parent::__construct($context, $access_manager, $router, $path_processor, $config_factory, $title_resolver, $current_user, $current_path, $path_matcher);
    $this->titleResolverManager = $title_resolver_manager;
    $this->titleResolvers = $this->titleResolverManager->getInstances();
    $this->breadcrumbConfig = $config_factory->get('breadcrumb_manager.config');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    return !$this->isExcludedPath($attributes->getRouteObject()->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url']);
    $breadcrumb->addCacheTags(['config:breadcrumb_manager.config']);
    $links = [];

    // Add the url.path.parent cache context. This code ignores the last path
    // part so the result only depends on the path parents.
    $breadcrumb->addCacheContexts(['url.path.parent']);

    // Do not display a breadcrumb on the frontpage.
    if ($this->pathMatcher->isFrontPage()) {
      return $breadcrumb;
    }

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);

    // Hide current page from breadcrumb if necessary.
    if (!$this->breadcrumbConfig->get('show_current')) {
      array_pop($path_elements);
    }

    $is_first = TRUE;
    while (count($path_elements) > 0) {
      $current_path = '/' . implode('/', $path_elements);
      array_pop($path_elements);

      // Check if we have to show last segment as link.
      $show_as_link = $is_first ? $this->breadcrumbConfig->get('show_current_as_link') : TRUE;
      $is_first = FALSE;

      // Copy the path elements for up-casting.
      $route_request = $this->getRequestForPath($current_path, $this->getExcludedPaths());
      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);

        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        if (!$access->isAllowed()) {
          continue;
        }

        $title = FALSE;
        foreach ($this->titleResolvers as $titleResolver) {
          if (!$titleResolver->isActive()) {
            continue;
          }
          $resolved_title = $titleResolver->getTitle($current_path, $route_request, $route_match);
          if (!empty($resolved_title)) {
            $title = $resolved_title;
            break;
          }
        }

        if ($title) {
          $url = $show_as_link ? Url::fromRouteMatch($route_match) : Url::fromRoute('<none>');
          $links[] = new Link($title, $url);
        }
      }
      elseif ($this->breadcrumbConfig->get('show_fake_segments')) {
        // Show fake segments if this option has been selected.
        try {
          $titleResolver = $this->titleResolverManager->createInstance('raw_path_component');
          // We don't really need a valid Request, so creating a simple one
          // will be more than necessary to let the plugin resolving the title.
          $current_request = Request::create($current_path);
          $title = $titleResolver->getTitle($current_path, $current_request, $route_match);
          $links[] = new Link($title, Url::fromRoute('<none>'));
        }
        catch (PluginException $e) {
          // Nothing to do here cause 'raw_path_component' will always exist.
        }
      }
    }

    // Add the Home link if necessary.
    if ($this->breadcrumbConfig->get('show_home')) {
      $home = $this->breadcrumbConfig->get('home') ?: $this->t('Home');
      $links[] = Link::createFromRoute($home, '<front>');
    }

    return $breadcrumb->setLinks(array_reverse($links));
  }

  /**
   * Set excluded paths.
   */
  protected function setExcludedPaths() {
    $excluded_paths = $this->breadcrumbConfig->get('excluded_paths');
    $this->excludedPaths = explode("\r\n", $excluded_paths);

    // Exclude Front page if not necessary.
    if (!$this->breadcrumbConfig->get('show_front')) {
      $this->excludedPaths[] = $this->config->get('page.front');
    }
  }

  /**
   * Get excluded paths.
   *
   * @return array
   *   An array of excluded paths.
   */
  protected function getExcludedPaths() {
    if (!isset($this->excludedPaths)) {
      $this->setExcludedPaths();
    }
    return $this->excludedPaths;
  }

  /**
   * Checks if the given path is excluded.
   *
   * @param string $path
   *   The path to be checked.
   *
   * @return bool
   *   A boolean indicating if the given path is excluded.
   */
  protected function isExcludedPath($path) {
    $excluded = implode("\n", $this->getExcludedPaths());
    return $this->pathMatcher->matchPath($path, $excluded);
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (in_array($path, $exclude)) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);

    if (empty($processed) || $this->isExcludedPath($processed)) {
      return NULL;
    }
    $this->currentPath->setPath($processed, $request);

    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }

}
