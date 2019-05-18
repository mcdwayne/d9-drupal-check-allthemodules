<?php

namespace Drupal\menu_pager\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Menu pager block.
 *
 * @Block(
 *   id = "menu_pager_block",
 *   admin_label = @Translation("Menu Pager"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\menu_pager\Plugin\Derivative\MenuBlock",
 * )
 */
class MenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a new MenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
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
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['menu_pager_restrict_to_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict to parent'),
      '#default_value' => isset($config['menu_pager_restrict_to_parent']) ? $config['menu_pager_restrict_to_parent'] : '',
      '#description' => $this->t('If checked, only previous and next links with the same menu parent as the active menu link will be used.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['menu_pager_restrict_to_parent'] = $values['menu_pager_restrict_to_parent'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_menu = $this->getDerivativeId();
    $config = $this->getConfiguration();

    // Show block if current page is in active menu trail of this menu and
    // previous or next links exist.
    $menu_link = $this->menuActiveTrail->getActiveLink(NULL);

    if (
      (isset($menu_link))
      && ($menu_link->getMenuName() == $block_menu)
      && ($navigation = $this->menuPagerGetNavigation($menu_link, $config['menu_pager_restrict_to_parent']))
      && (isset($navigation['previous']) || isset($navigation['next']))
    ) {
      $items = [];

      // Previous link.
      if (!empty($navigation['previous'])) {
        $link_title_previous = [
          '#theme' => 'menu_pager_previous',
          '#title' => $navigation['previous']['link_title']
        ];

        $items['previous'] = [
          '#markup' => Link::fromTextAndUrl($link_title_previous, $navigation['previous']['url'])->toString(),
          '#wrapper_attributes' => ['class' => 'menu-pager-previous'],
        ];
      }

      // Next link.
      if (!empty($navigation['next'])) {
        $link_title_next = [
          '#theme' => 'menu_pager_next',
          '#title' => $navigation['next']['link_title']
        ];

        $items['next'] = [
          '#markup' => Link::fromTextAndUrl($link_title_next, $navigation['next']['url'])->toString(),
          '#wrapper_attributes' => ['class' => 'menu-pager-next'],
        ];
      }

      return [
        '#theme' => 'item_list',
        '#items' => $items,
        '#attributes' => ['class' => ['menu-pager', 'clearfix']],
        '#attached' => ['library' => ['menu_pager/menu_pager']],
      ];

    }
  }

  /**
   * Returns array with previous and next links for a given $menu_link.
   *
   * @param object $menu_link
   *   A menu link object.
   * @param bool $restrict_to_parent
   *   (optional) A boolean to indicate whether or not to restrict the previous
   *   and next links to the menu's parent. Defaults to FALSE.
   *
   * @return array
   *   An array with 'previous' and 'next' links, if found.
   */
  public function menuPagerGetNavigation($menu_link, $restrict_to_parent = FALSE) {
    $navigation = &drupal_static(__FUNCTION__, []);
    $menu_name = $menu_link->getMenuName();

    if (!isset($navigation[$menu_name])) {
      // Build flat tree of main menu links.
      $parameters = new MenuTreeParameters();
      $parameters->expandedParents;

      $tree = $this->menuTree->load($menu_name, $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuTree->transform($tree, $manipulators);

      $ignore = $this->menuPagerIgnorePaths($menu_name);

      $flat_links = [];
      $this->menuPagerFlattenTree($tree, $flat_links, $ignore);

      // Find previous and next links.
      while ($flat_link = current($flat_links)) {
        if ($flat_link['mlid'] === $menu_link->getPluginId()) {
          if (key($flat_links) === 0) {
            $previous = FALSE;
          }
          else {
            $previous = prev($flat_links);
            next($flat_links);
          }
          $next = next($flat_links);
          $plid = '';
          // Add if found and not restricting to parent, or both links share
          // same parent.
          if ($parent = $menu_link->getParent()) {
            $parent = $this->menuLinkManager->createInstance($parent);
            $plid = $parent->getPluginId();
          }
          if ($previous && (!$restrict_to_parent || $previous['plid'] === $plid)) {
            $navigation[$menu_name]['previous'] = $previous;
          }
          if ($next && (!$restrict_to_parent || $next['plid'] === $plid)) {
            $navigation[$menu_name]['next'] = $next;
          }
        }
        else {
          next($flat_links);
        }
      }
    }

    return $navigation[$menu_name];
  }

  /**
   * Recursively flattens tree of menu links.
   */
  public function menuPagerFlattenTree($menu_links, &$flat_links, $ignore, $plid = '') {
    $menu_links = array_values($menu_links);
    foreach ($menu_links as $item) {
      $uuid = $item->link->getPluginId();
      $link_title = $item->link->getTitle();
      $url = $item->link->getUrlObject();
      $link_rote = $item->link->getRouteName();
      $link_path = $url->toString();
      if (!in_array($link_rote, $ignore) && !in_array($link_path, $ignore) && $item->link->isEnabled()) {
        $flat_links[] = [
          'mlid' => $uuid,
          'plid' => $plid,
          'link_path' => $link_path,
          'link_title' => $link_title,
          'url' => $url,
        ];
      }

      if ($item->hasChildren) {
        $this->menuPagerFlattenTree($item->subtree, $flat_links, $ignore, $uuid);
      }
    }
  }

  /**
   * Define paths to NOT include in the pager.
   */
  function menuPagerIgnorePaths($menu_name) {
    $paths = &drupal_static(__FUNCTION__, []);

    if (!isset($paths[$menu_name])) {
      $module_handler = \Drupal::moduleHandler();
      $paths[$menu_name] = $module_handler->invokeAll('menu_pager_ignore_paths', [$menu_name]);
      $module_handler->alter('menu_pager_ignore_paths', $paths[$menu_name], $menu_name);
    }

    return $paths[$menu_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }
}
