<?php

namespace Drupal\breadcrumb_menu;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class BreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  protected $menuActiveTrail;

  protected $menuLinkManager;

  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, MenuActiveTrailInterface $menu_active_trail, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($context, $access_manager, $router, $path_processor, $config_factory, $title_resolver, $current_user, $current_path);
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
  }

  public function build(RouteMatchInterface $route_match) {
    $links = parent::build($route_match);

    $menu_link_titles = [];
    foreach ($this->menuActiveTrail->getActiveTrailIds('main') as $menu_link_id) {
      if (!empty($menu_link_id)) {
        /** @var \Drupal\Core\Menu\MenuLinkInterface $menu_link */
        $menu_link = $this->menuLinkManager->createInstance($menu_link_id);
        $menu_link_titles[$menu_link->getUrlObject()->toString()] = $menu_link->getTitle();
      }
    }

    foreach ($links->getLinks() as $link) {
      $hash = $link->getUrl()->toString();
      if (isset($menu_link_titles[$hash])) {
        $link->setText($menu_link_titles[$hash]);
      }
    }

    return $links;
  }
}
