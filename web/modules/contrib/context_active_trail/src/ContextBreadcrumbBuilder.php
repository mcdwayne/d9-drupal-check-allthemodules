<?php

namespace Drupal\context_active_trail;

use Drupal\context\ContextManager;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Build breadcrumbs based on active trail from context.
 */
class ContextBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The active trail.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $activeTrail;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $linkManager;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Configuration of the context reaction affecting breadcrumbs.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructor.
   *
   * @param \Drupal\context\ContextManager $context_manager
   *   The context manager.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $active_trail
   *   The active trail.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ContextManager $context_manager, MenuActiveTrailInterface $active_trail, MenuLinkManagerInterface $link_manager, TitleResolverInterface $title_resolver, RequestStack $request_stack) {
    $this->contextManager = $context_manager;
    $this->activeTrail = $active_trail;
    $this->linkManager = $link_manager;
    $this->titleResolver = $title_resolver;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    foreach ($this->contextManager->getActiveReactions('active_trail') as $reaction) {
      if ($reaction->setsBreadcrumbs()) {
        return $this->configuration = $reaction->getConfiguration();
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path']);

    // Start with home page.
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));

    // Add links from menu.
    $link_ids = array_filter($this->activeTrail->getActiveTrailIds(NULL));
    foreach (array_reverse($link_ids) as $link_id) {
      $link = $this->linkManager->getInstance(['id' => $link_id]);
      $breadcrumb->addLink(
        Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())
      );
    }

    // Include current page title.
    if ($this->configuration['breadcrumb_title']) {
      $title = $this->titleResolver->getTitle($this->request,
        $route_match->getRouteObject());
      $breadcrumb->addLink(Link::createFromRoute($title, '<none>'));
    }

    return $breadcrumb;
  }

}
