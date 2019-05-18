<?php

namespace Drupal\entity_toolbar\Toolbar;

use Drupal\Core\Url;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_toolbar\Entity\EntityToolbarConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Toolbar integration handler.
 */
class EntityToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager, AccountProxyInterface $account, RouteProviderInterface $route_provider, LoggerChannelFactoryInterface $logger_factory) {
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
    $this->account = $account;
    $this->routeProvider = $route_provider;
    $this->loggerFactory = $logger_factory;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('toolbar.menu_tree'),
      $container->get('plugin.manager.menu.link'),
      $container->get('current_user'),
      $container->get('router.route_provider'),
      $container->get('logger.factory')
    );
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The entity toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {

    $items = [];

    $entity_toolbars = \Drupal::entityTypeManager()
      ->getStorage('entity_toolbar')
      ->loadMultiple();

    /** @var \Drupal\entity_toolbar\Entity\EntityToolbarConfig $toolbar */
    foreach ($entity_toolbars as $toolbar) {

      if (!$toolbar->status()) {
        continue;
      }

      if (!$this->ajaxRouteExists($toolbar)) {
        continue;
      }

      $baseRouteName = $toolbar->get('baseRouteName');

      $build = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['toolbar-menu-administration', 'entity-toolbar-menu'],
          'id' => ['entity-toolbar-placeholder-' . $toolbar->id()],
          'data-toolbar-id' => [$toolbar->id()],
        ],
        '#attached' => ['library' => ['entity_toolbar/entity-toolbar']],
      ];

      $build[] = ['#markup' => '<ul class="toolbar-menu"><li class="menu-item menu-item--expanded"><a href="#"></a></li></ul>'];

      $items[$toolbar->id()] = [
        '#cache' => [
          'tags' => $toolbar->getCacheTags(),
          'contexts' => ['user.permissions'],
        ],
      ];

      $url = Url::fromRoute($baseRouteName);

      if ($url->access($this->account) && $this->account->hasPermission('access toolbar')) {
        $items[$toolbar->id()] += [
          '#type' => 'toolbar_item',
          '#weight' => !empty($toolbar->get('weight')) ? $toolbar->get('weight') : 0,
          'tab' => [
            '#type' => 'link',
            '#title' => $toolbar->label(),
            '#url' => $url,
            '#attributes' => [
              'title' => $toolbar->label(),
              'class' => ['toolbar-icon', 'toolbar-icon-entity-toolbar'],
            ],
          ],
          'tray' => [
            '#heading' => $toolbar->label(),
            'entity_bundle_menu' => $build,
          ],
        ];
      }
    }

    return $items;
  }

  /**
   * Check if ajax route properly set up in EntityToolbarConfigRouteProvider.
   *
   * @param \Drupal\entity_toolbar\Entity\EntityToolbarConfig $toolbar
   *   EntityToolbarConfig entity.
   *
   * @return bool
   *   Boolean value.
   */
  protected function ajaxRouteExists(EntityToolbarConfig $toolbar) {
    // Verify ajax route exists.
    try {
      $verifyAjaxRoute = $this->routeProvider
        ->getRouteByName('entity_toolbar.ajax.' . $toolbar->id());

      if (!empty($verifyAjaxRoute)) {
        return TRUE;
      }
    }
    catch (RouteNotFoundException $e) {
      $this->loggerFactory
        ->get('entity_toolbar')
        ->error('entity_toolbar.ajax.' . $toolbar->id() . ' route missing.');
      return FALSE;
    }
  }

}
