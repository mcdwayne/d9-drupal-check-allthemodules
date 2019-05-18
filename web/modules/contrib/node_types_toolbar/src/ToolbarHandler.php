<?php

namespace Drupal\node_types_toolbar;

use Drupal\Core\Url;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkManagerInterface;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler implements ContainerInjectionInterface {

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
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager, AccountProxyInterface $account) {
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
    $this->account = $account;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('toolbar.menu_tree'),
      $container->get('plugin.manager.menu.link'),
      $container->get('current_user')
    );
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The node types toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {

    $menu_links = $this->menuLinkManager
      ->loadLinksByRoute('entity.node_type.collection');

    $link = $menu_links['entity.node_type.collection'];

    // Mimics the admin_toolbar classes to use the drop down menus in
    // the admin_toolbar/toolbar.tree library.
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => 'toolbar-menu-administration'],
      '#attached' => ['library' => ['node_types_toolbar/node_types_toolbar']],
    ];

    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMaxDepth(4);
    $menu_parameters->setRoot($link->getPluginId());
    $menu_parameters->excludeRoot();

    $tree = $this->menuLinkTree->load('admin', $menu_parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $tree_build = $this->menuLinkTree->build($tree);

    $tree_build['#cache']['tags'][] = 'config:node_type_list';

    $build[] = $tree_build;

    $items['node_types'] = [
      '#cache' => [
        'tags' => ['config:node_type_list'],
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->account->hasPermission('administer content types') && $this->account->hasPermission('access toolbar')) {
      $items['node_types'] += [
        '#type' => 'toolbar_item',
        '#weight' => -14,
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Node Types'),
          '#url' => Url::fromRoute('entity.node_type.collection'),
          '#attributes' => [
            'title' => $this->t('Node Types'),
            'class' => ['toolbar-icon', 'toolbar-icon-node-types'],
          ],
        ],
        'tray' => [
          '#heading' => $this->t('Node Types'),
          'node_types_menu' => $build,
        ],
      ];
    }

    return $items;
  }

}
