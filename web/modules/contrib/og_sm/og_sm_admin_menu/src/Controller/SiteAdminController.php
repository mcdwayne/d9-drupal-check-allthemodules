<?php

namespace Drupal\og_sm_admin_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site admin routes.
 */
class SiteAdminController extends ControllerBase {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The route match object for the current page.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs a \Drupal\Core\Menu\MenuActiveTrail object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A route match object for finding the active link.
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, SystemManager $systemManager, TranslationInterface $string_translation) {
    $this->menuLinkManager = $menu_link_manager;
    $this->routeMatch = $route_match;
    $this->systemManager = $systemManager;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('current_route_match'),
      $container->get('system.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Title callback for the overview pages.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Site node to use to add the Site name to the title.
   * @param array $_title_arguments
   *   Optional array from the route defaults.
   *
   * @return string
   *   The personalized title.
   */
  public function siteAdminMenuTitle(NodeInterface $node, array $_title_arguments) {
    // @codingStandardsIgnoreStart
    return $this->t($_title_arguments['title'], ['@site_title' => $node->label()]);
    // @codingStandardsIgnoreEnd
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function siteAdminMenuBlockPage() {
    $link = NULL;

    $menu_name = 'og_sm_admin_menu';
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name) {
      // Load links matching this route.
      $links = $this->menuLinkManager->loadLinksByRoute($route_name, [], $menu_name);
      // Select the first matching link.
      if ($links) {
        $link = reset($links);
      }
    }

    if ($link && $content = $this->systemManager->getAdminBlock($link)) {
      $output = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }
    else {
      $output = [
        '#markup' => $this->t('You do not have any administrative items.'),
      ];
    }

    return $output;
  }

}
