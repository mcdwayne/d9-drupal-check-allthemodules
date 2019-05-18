<?php

namespace Drupal\flyout_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides the Flyout menu block.
 *
 * @Block(
 *   id = "flyout_menu",
 *   admin_label = @Translation("Flyout menu"),
 *   category = @Translation("Menus")
 * )
 */
class FlyoutMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, MenuLinkTreeInterface $menu_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->getEditable('flyout_menu.settings');
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->config->get('menu');

    $parameters = $this->menuTree
      ->getCurrentRouteMenuTreeParameters($menu_name);
    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      [
        'callable' => 'menu.default_tree_manipulators:checkAccess',
      ],
      [
        'callable' => 'menu.default_tree_manipulators:generateIndexAndSort',
      ],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);
    $build['#theme'] = 'flyout_menu';
    $build['#attached'] = [
      'library' => [
        'flyout_menu/menu',
        'flyout_menu/styling',
      ],
      'drupalSettings' => [
        'flyout_menu' => [
          'breakpoint' => $this->config->get('breakpoint'),
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:block.block.flyoutmenu';

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $menu_name = $this->config->get('menu');

    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . $menu_name]);
  }

}
