<?php

namespace Drupal\wisski_core;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WissKI Toolbar integration handler.
 */
class WisskiToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The devel toolbar config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * WissKI ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, ConfigFactoryInterface $config_factory, AccountProxyInterface $account) {
    $this->menuLinkTree = $menu_link_tree;
    $this->config = $config_factory->get('wisski.toolbar.settings');
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('toolbar.menu_tree'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The wisski toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {
    $items['wisski'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->account->hasPermission('view any wisski content')) {
      $items['wisski'] += [
        '#type' => 'toolbar_item',
        '#weight' => 999,
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('WissKI'),
          '#url' => Url::fromRoute('entity.wisski_individual_create.list'),
          '#attributes' => [
            'title' => $this->t('WissKI menu'),
            'class' => ['toolbar-icon', 'toolbar-icon-wisski'],
          ],
        ],
        'tray' => [
          '#heading' => $this->t('WissKI menu'),
          'wisski_menu' => [
            // Currently wisski menu is uncacheable, so instead of poisoning the
            // entire page cache we use a lazy builder.
            // @see \Drupal\wisski\Plugin\Menu\DestinationMenuLink
            // @see \Drupal\wisski\Plugin\Menu\RouteDetailMenuItem
            '#lazy_builder' => [WisskiToolbarHandler::class . ':lazyBuilder', []],
            // Force the creation of the placeholder instead of rely on the
            // automatical placeholdering or otherwise the page results
            // uncacheable when max-age 0 is bubbled up.
            '#create_placeholder' => TRUE,
          ],

          'configuration' => [
            '#type' => 'link',
            '#title' => $this->t('Configure'),
            '#url' => Url::fromRoute('wisski.config_menu'),
            '#options' => [
              'attributes' => ['class' => ['edit-wisski-toolbar']],
            ],
          ],
        ],
        '#attached' => [
          'library' => 'wisski_core/wisski-toolbar',
        ],
      ];
    }

    return $items;
  }

  /**
   * Lazy builder callback for the wisski menu toolbar.
   *
   * @return array
   *   The renderable array rapresentation of the wisski menu.
   */
  public function lazyBuilder() {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks()->setTopLevelOnly();

    $tree = $this->menuLinkTree->load('wisski', $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $build = $this->menuLinkTree->build($tree);

    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($this->config)
      ->applyTo($build);

    return $build;
  }

}
