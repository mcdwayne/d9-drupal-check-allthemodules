<?php

namespace Drupal\cheeseburger_menu\Controller;

/**
 * @file
 * Controller used for rendering block.
 */

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\system\Entity\Menu;
use Drupal\block\Entity\Block;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\breakpoint\BreakpointManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class RenderCheeseburgerMenuBlock.
 *
 * @package Drupal\cheeseburger_menu\Controller
 */
class RenderCheeseburgerMenuBlock extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuTree;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * The breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManager
   */
  protected $breakPointManager;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerChannelFactory;

  /**
   * RenderCheeseburgerMenuBlock constructor.
   */
  public function __construct(Renderer $renderer,
                              MenuLinkTree $menuLinkTree,
                              ThemeHandler $themeHandler,
                              BreakpointManager $breakpointManager,
                              RouteMatchInterface $route_match,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->renderer = $renderer;
    $this->menuTree = $menuLinkTree;
    $this->themeHandler = $themeHandler;
    $this->breakPointManager = $breakpointManager;
    $this->routeMatch = $route_match;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('menu.link_tree'),
      $container->get('theme_handler'),
      $container->get('breakpoint.manager'),
      $container->get('current_route_match'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content(Request $request) {
    $block_id = $request->request->get('block_id');
    $current_route = $request->request->get('current_route');
    $block = Block::load($block_id);
    if (!$block) {
      return new Response('<div>No such block</div>', 403);
    }
    $config = $block->get('settings');
    $tree = $this->renderTree($config, $current_route);
    $rendered_tree = $this->renderer->render($tree);

    return new Response($rendered_tree);
  }

  /**
   * Render given tree.
   */
  public function renderTree($config, $current_route) {
    $menus = [];
    foreach (['menu' => 'Menu', 'taxonomy_vocabulary' => 'Vocabulary'] as $menu_key => $menu_label) {
      foreach ($config[$menu_key] as $id => $data) {
        switch ($data['title']) {
          case 'do_not_show':
            $title = '';
            $menu = $this->entityTypeManager()->getStorage($menu_key)->load($id);
            $navigation_title = $menu->label();
            break;

          case 'use_default':
            $menu = $this->entityTypeManager()->getStorage($menu_key)->load($id);
            $navigation_title = $title = $menu->label();
            break;

          case 'manual':
            $navigation_title = $title = $data['manual_title'];
            break;
        }
        $menus[] = [
          'tree' => $this->{'get' . $menu_label . 'Tree'}($id),
          'id' => $id,
          'menu_weight' => $data['menu_weight'],
          'title' => $title,
          'collapsible_title' => $data['collapsible_title'] ? '' : ' cheeseburger-menu__menu-list-item--expanded',
          'navigation_title' => $navigation_title,
        ];
      }
    }
    $additional_menus = [];
    // Cart show.
    if (array_key_exists('cart', $config) && $config['cart']['show']) {
      $additional_menus[] = [
        'id' => 'cart',
        'menu_weight' => $config['cart']['menu_weight'],
        'url' => '/cart',
        'title' => '',
        'navigation_title' => $this->t('Cart'),
      ];
    }

    // Phone show.
    if (array_key_exists('phone', $config) && $config['phone']['show']) {
      if ($config['phone']['store'] == 0) {
        $additional_menus[] = [
          'id' => 'phone',
          'menu_weight' => $config['phone']['menu_weight'],
          'url' => 'tel:' . $config['phone']['manual_title'],
          'title' => '',
          'navigation_title' => $this->t('Phone'),
        ];
      }
      else {
        if ($this->moduleHandler()->moduleExists('commerce_store')) {
          $store = Store::load($config['phone']['store']);
          if (!empty($store) && $store->hasField('field_phone')) {
            if (!$store->get('field_phone')->isEmpty()) {
              $additional_menus[] = [
                'id' => 'phone',
                'menu_weight' => $config['phone']['menu_weight'],
                'url' => 'tel:' . $store->get('field_phone')->value,
                'title' => '',
                'navigation_title' => $this->t('Phone'),
              ];
            }
          }
        }
      }
    }

    if (array_key_exists('lang_switcher', $config) && $config['lang_switcher']['show']) {
      /** @var \Drupal\Core\Language\LanguageManager $languageManager */
      $languageManager = $this->languageManager();
      $languageTree = [];
      $languages = $languageManager->getLanguages();
      foreach ($languages as $language) {
        $languageTree[] = [
          'title' => $language->getName(),
          'id' => $language->getId(),
          'params' => [],
          'url' => $language->isDefault() ? '/' : '/' . $language->getId(),
          'children' => [],
          'entity_type_id' => 'lang',
        ];
      }

      $additional_menus[] = [
        'id' => 'lang-switcher',
        'menu_weight' => $config['lang_switcher']['menu_weight'],
        'navigation_title' => $this->t('Language switcher'),
        'title' => $this->t('Language switcher'),
        'tree' => $languageTree,
      ];
    }

    $tree = array_merge($menus, $additional_menus);
    $this->formTree($tree, $current_route, $config['active_state_enable']);
    $render = [
      '#theme' => 'cheeseburger_menu',
      '#tree' => $tree,
      '#show_navigation' => $config['show_navigation'],
    ];

    if ($config['active_state_enable']) {
      $render['#cache']['contexts'] = [
        'url',
      ];
    }
    return $render;
  }

  /**
   * Handles forming tree for menus.
   */
  public function formTree(&$tree, $current_url = FALSE, $active_state = TRUE) {
    $count_active = 0;
    if ($active_state) {
      foreach ($tree as $tree_key => $menu) {
        if (!array_key_exists('tree', $menu)) {
          continue;
        }
        $this->activateMenuItem($tree[$tree_key]['tree'], $count_active, FALSE, $current_url);
      }

      if ($count_active === 0) {
        foreach ($tree as $tree_key => $menu) {
          if (!array_key_exists('tree', $menu)) {
            continue;
          }
          $this->activateMenuItem($tree[$tree_key]['tree'], $count_active, TRUE, $current_url);
        }
      }
    }
    $this->sortMenus($tree);
  }

  /**
   * If menu is active it activates it.
   */
  public function activateMenuItem(&$menu, &$count_active, $pos = FALSE, $url = FALSE) {
    if ($url === FALSE) {
      $url = Url::fromRouteMatch($this->routeMatch)->toString();
    }
    foreach ($menu as $item_key => $menu_item) {
      if ($pos) {
        if (!empty($menu_item['url']) && strpos($url, $menu_item['url']) !== FALSE) {
          if (($url == '/' && $menu_item['url'] == '/') || $menu_item['url'] != '/') {
            $menu[$item_key]['active'] = 'active';
            $count_active++;
          }
        }
        else {
          $menu[$item_key]['active'] = '';
        }
      }
      else {
        if ($menu_item['url'] == $url) {
          $menu[$item_key]['active'] = 'active';
          $count_active++;
        }
        else {
          $menu[$item_key]['active'] = '';
        }
      }
      $this->activateMenuItem($menu[$item_key]['children'], $count_active, $pos, $url);
    }
  }

  /**
   * Sorts menu based on menu_weight.
   */
  public function sortMenus(&$tree) {
    do {
      $change = FALSE;
      for ($i = 0; $i < (count($tree) - 1); $i++) {
        if ($tree[$i]['menu_weight'] > $tree[$i + 1]['menu_weight']) {
          $temp = $tree[$i];
          $tree[$i] = $tree[$i + 1];
          $tree[$i + 1] = $temp;
          $change = TRUE;
        }
      }
    } while ($change === TRUE);
  }

  /**
   * Returning menu tree data.
   */
  public function getMenuTree($menu) {
    $menu_tree = $this->menuTree;
    $menu_tree_parameters = new MenuTreeParameters();
    $menu_tree_parameters->onlyEnabledLinks();
    $tree = $menu_tree->load($menu, $menu_tree_parameters);
    $manipulators = [
      [
        'callable' => 'menu.default_tree_manipulators:checkAccess',
      ],
      [
        'callable' => 'menu.default_tree_manipulators:generateIndexAndSort',
      ],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $menu_build = $menu_tree->build($tree);
    $new_tree = [];
    if (array_key_exists('#items', $menu_build)) {
      if (is_array($menu_build['#items']) || is_object($menu_build['#items'])) {
        $new_tree = $this->formatMenuArray($menu_build['#items']);
      }
    }

    return $new_tree;
  }

  /**
   * Formats menu.
   */
  public function formatMenuArray($items) {
    $new_tree = [];
    foreach ($items as $menu_data) {
      $temp_array = [];
      if ($menu_data['url']->isRouted()) {
        $temp_array['title'] = $menu_data['title'];
        if (method_exists($menu_data['url'], 'getRouteParameters')) {
          $temp_array['params'] = $menu_data['url']->getRouteParameters();
        }
        $url = Url::fromRoute($menu_data['url']->getRouteName(), $temp_array['params']);
        $temp_array['url'] = $url->toString();
        $temp_array['route_name'] = $menu_data['url']->getRouteName();
        $temp_array['entity_type_id'] = 'menu_item';
      }
      elseif ($menu_data['url']->isExternal()) {
        $temp_array['title'] = $menu_data['title'];
        $temp_array['url'] = $menu_data['url']->toUriString();
        $temp_array['route_name'] = 'external_link';
        $temp_array['entity_type_id'] = 'menu_item';
      }
      else {
        $this->loggerChannelFactory->get('cheeseburger_menu')->warning('Cheeseburger menu was not able to recognize menu link as external or internal. Menu link name: '
          . $menu_data['title'] . '. Maybe go and save menu link again on current language.');
        continue;
      }
      $temp_array['children'] = [];
      if (array_key_exists('below', $menu_data)) {
        $temp_array['children'] = $this->formatMenuArray($menu_data['below']);
      }
      $new_tree[] = $temp_array;
    }
    return $new_tree;
  }

  /**
   * Formats vocabulary.
   */
  public function getVocabularyTree($vocabulary) {
    $vocabulary_tree = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
    $tree = [];
    $parents = [];
    foreach ($vocabulary_tree as $term) {
      if (reset($term->parents) == 0) {
        $parents[] = $term;
      }
    }
    foreach ($parents as $term) {
      $tree[] = $this->findVocabularyChild($this->entityTypeManager()->getStorage('taxonomy_term')->load($term->tid));
    }

    return $tree;
  }

  /**
   * Searches for vocabulary child.
   */
  public function findVocabularyChild($term) {

    $icon = FALSE;
    if ($term->hasField('field_icon')) {
      if (!empty($term->get('field_icon')->getValue())) {
        $icon = $term->get('field_icon')->entity->getFileUri();
        $icon = file_create_url($icon);
      }
    }

    $langcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $translation_languages = $term->getTranslationLanguages();
    if (array_key_exists($langcode, $translation_languages)) {
      $translation = $term->getTranslation($langcode);
    }
    else {
      $translation = $term;
    }

    $term_tree = [
      'id' => $translation->get('tid')->value,
      'title' => $translation->getName(),
      'url' => $translation->url(),
      'entity_type_id' => $translation->getEntityTypeId(),
      'icon' => $icon,
      'children' => [],
    ];
    $ancestors = $this->entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadChildren($term->get('tid')->value);

    foreach ($ancestors as $ancestor) {
      $term_tree['children'][] = $this->findVocabularyChild($ancestor);
    }
    return $term_tree;
  }

  /**
   * Get all menu link names.
   */
  public function getAllMenuLinkNames() {
    $all_menus = Menu::loadMultiple();
    $menus = [];
    foreach ($all_menus as $id => $menu) {
      $menus[$id] = $menu->label();
    }
    asort($menus);
    return $menus;
  }

  /**
   * Get all taxonomy term names.
   */
  public function getAllTaxonomyTermNames() {
    if (!$this->moduleHandler()->moduleExists('taxonomy')) {
      return [];
    }
    $all_taxonomies = $this->entityTypeManager()
      ->getStorage('taxonomy_vocabulary')
      ->loadMultiple();
    $menus = [];
    foreach ($all_taxonomies as $id => $menu) {
      $menus[$id] = $menu->label();
    }
    asort($menus);
    return $menus;
  }

  /**
   * Returning breakpoint data for default theme.
   */
  public function returnBreakpointsForDefaultTheme() {
    /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
    $theme_handler = $this->themeHandler;
    /** @var \Drupal\breakpoint\BreakpointManager $breakpoint_manager */
    $breakpoint_manager = $this->breakPointManager;
    $groups = $breakpoint_manager->getGroups();
    $list = [];
    foreach ($groups as $group) {
      if (is_object($group)) {
        $group = $group->getUntranslatedString();
      }
      $breakpoints = $breakpoint_manager->getBreakpointsByGroup($group);
      foreach ($breakpoints as $key => $breakpoint) {
        if ($breakpoint->getProvider() == $theme_handler->getDefault()) {
          $list[$key]['mediaQuery'] = $breakpoint->getMediaQuery();
          $list[$key]['label'] = $breakpoint->getLabel();
          if (is_object($list[$key]['label'])) {
            $list[$key]['label'] = $list[$key]['label']->__toString();
          }
        }
      }
    }
    return $list;
  }

}
