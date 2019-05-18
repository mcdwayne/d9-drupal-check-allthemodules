<?php

namespace Drupal\entity_toolbar\Controller;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\entity_toolbar\Entity\EntityToolbarConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\entity_toolbar\Ajax\EntityToolbarLoadedCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\RendererInterface;

/**
 * Class ToolbarController.
 *
 * @package Drupal\admin_toolbar_tools\Controller
 */
class ToolbarController extends ControllerBase {
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
   * The toolbar cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $toolbarCache;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, MenuLinkManagerInterface $menu_link_manager, AccountProxyInterface $account, RendererInterface $renderer) {
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
    $this->account = $account;
    $this->toolbarCache = $this->cache('toolbar');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('toolbar.menu_tree'),
      $container->get('plugin.manager.menu.link'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * Return entity toolbar as ajax.
   *
   * @param \Drupal\entity_toolbar\Entity\EntityToolbarConfig $toolbar
   *   Entity Toolbar config entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function lazyLoad(EntityToolbarConfig $toolbar) {

    $response = new AjaxResponse();

    // Using cache_toolbar rather than cache_render, since
    // developers usually turn off the render cache while developing,.
    $cid = 'entity_toolbar:' . $toolbar->id() . '.data';

    if ($cache = $this->toolbarCache->get($cid)) {
      $build = $cache->data;
    }
    else {
      $build = $this->renderToolbar($toolbar);
      $cache_tags = $build[0]['#cache']['tags'];
      $build = $this->renderer->renderPlain($build);
      $this->toolbarCache
        ->set($cid, $build, Cache::PERMANENT, $cache_tags);
    }

    $response->addCommand(new ReplaceCommand('#entity-toolbar-placeholder-' . $toolbar->id(), $build));

    $response->addCommand(new EntityToolbarLoadedCommand('toolbar-tab-' . $toolbar->id()));

    return $response;

  }

  /**
   * Render entity toolbar content.
   *
   * @param \Drupal\entity_toolbar\Entity\EntityToolbarConfig $toolbar
   *   Entity Toolbar config entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  protected function renderToolbar(EntityToolbarConfig $toolbar) {
    $baseRouteName = $toolbar->get('baseRouteName');

    $menu_links = $this->menuLinkManager
      ->loadLinksByRoute($baseRouteName);

    $link = $menu_links[$baseRouteName];

    // Mimics the admin_toolbar classes to use the drop down menus in
    // the admin_toolbar/toolbar.tree library.
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['toolbar-menu-administration', 'entity-toolbar-menu']],
      '#attached' => ['library' => ['entity_toolbar/entity-toolbar']],
    ];

    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMaxDepth(4);
    $menu_parameters->setRoot($link->getPluginId());
    $menu_parameters->excludeRoot();

    $tree = $this->menuLinkTree->load('entity-toolbar', $menu_parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $tree_build = $this->menuLinkTree->build($tree);

    // Add weight to items to prevent them being re-ordered
    // by key in menu--toolbar.html.twig.
    $index = 0;
    foreach ($tree_build['#items'] as $key => $value) {
      $value['#weight'] = $index;
      $tree_build['#items'][$key] = $value;
      $index++;
    }

    // Break up into groups of no more than seven.
    $toolbar_items = $tree_build['#items'];

    if (!empty($toolbar_items)) {
      foreach ($toolbar_items as $key => &$link) {
        // We don't ever want to set active class on the clones of the
        // collection page used for each letter, or else all of them
        // will be highlit on the collection page.
        $link['url']->setOption('set_active_class', FALSE);

        if (count($link['below']) > 7) {
          $copy = $link;
          $copy['#weight'] = $link['#weight'] + .1;
          $copy['below'] = array_slice($copy['below'], 7, NULL, TRUE);
          $link['below'] = array_slice($link['below'], 0, 7, TRUE);
          $toolbar_items[$key . '.' . $copy['title']] = $copy;
        }

        if (empty($link['title'])) {
          continue;
        }
      }
    }

    uasort($toolbar_items, ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $tree_build['#items'] = $toolbar_items;

    $bundleDefinition = $this->entityTypeManager()->getDefinition($toolbar->get('bundleEntityId'));
    if ($bundleDefinition) {
      $toolbar->addCacheTags($bundleDefinition->getListCacheTags());
    }

    $tree_build['#cache']['tags'] += $toolbar->getCacheTags();

    $build[] = $tree_build;

    return $build;
  }

}
