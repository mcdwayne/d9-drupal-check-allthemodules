<?php

namespace Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver;

use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MenuLinkTitle.
 *
 * @BreadcrumbTitleResolver(
 *   id = "menu_link_title",
 *   label = @Translation("Menu Link"),
 *   description = @Translation("Resolve title from Menu link if exists."),
 *   weight = 0
 * )
 *
 * @package Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver
 */
class MenuLinkTitle extends BreadcrumbTitleResolverBase {

  /**
   * The Menu Link Manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($path, Request $request, RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $route_parameters = $route_match->getRawParameters()->all();
    $menu_links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters);

    if (empty($menu_links)) {
      return FALSE;
    }

    $titles = [];
    foreach ($menu_links as $menu_link) {
      $menu = $menu_link->getMenuName();
      $titles[$menu] = $menu_link->getTitle();
    }
    return isset($titles['main']) ? $titles['main'] : reset($titles);
  }

}
