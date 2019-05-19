<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements UltimenuManagerInterface.
 */
class UltimenuManager extends UltimenuBase implements UltimenuManagerInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Static cache for the menu blocks.
   *
   * @var array
   */
  protected $menuBlocks;

  /**
   * Static cache for the blocks.
   *
   * @var array
   */
  protected $blocks;

  /**
   * Static cache for the regions.
   *
   * @var array
   */
  protected $regions;

  /**
   * Static cache for the enabled regions.
   *
   * @var array
   */
  protected $enabledRegions;

  /**
   * Static cache for the enabled regions filtered by menu.
   *
   * @var array
   */
  protected $regionsByMenu;

  /**
   * Static cache for the menu options.
   *
   * @var array
   */
  protected $menuOptions;

  /**
   * The Ultimenu tree service.
   *
   * @var \Drupal\ultimenu\UltimenuTree
   */
  protected $tree;

  /**
   * The Ultimenu tool service.
   *
   * @var \Drupal\ultimenu\UltimenuTool
   */
  protected $tool;

  /**
   * Constructs a Ultimenu object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BlockManagerInterface $block_manager, ModuleHandlerInterface $module_handler, RendererInterface $renderer, UltimenuTreeInterface $tree, UltimenuToolInterface $tool) {
    parent::__construct($config_factory, $entity_type_manager, $block_manager);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->tree = $tree;
    $this->tool = $tool;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.block'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('ultimenu.tree'),
      $container->get('ultimenu.tool')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Returns the renderer.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Returns the tool service.
   */
  public function getTool() {
    return $this->tool;
  }

  /**
   * Returns the tool service.
   */
  public function getTree() {
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenus() {
    if (!isset($this->menuOptions)) {
      $this->menuOptions = $this->tree->getMenus();
    }
    return $this->menuOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getUltimenuBlocks() {
    if (!isset($this->menuBlocks)) {
      $this->menuBlocks = [];
      foreach ($this->getMenus() as $delta => $nice_name) {
        if ($this->getEnabledBlocks($delta)) {
          $this->menuBlocks[$delta] = $this->t('@name', ['@name' => $nice_name]);
        }
      }
      asort($this->menuBlocks);
    }
    return $this->menuBlocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBlocks($menu_name) {
    $blocks = $this->getSetting('blocks');
    return !empty($blocks[$menu_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    $build = [
      '#theme'      => 'ultimenu',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderBuild']],
    ];

    $this->moduleHandler->alter('ultimenu_build', $build);
    return $build;
  }

  /**
   * Builds the Ultimenu outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element) {
    $build = $element['#build'];
    $config = $build['config'];
    $config['current_path'] = Url::fromRoute('<current>')->toString();
    $goodies = $this->getSetting('goodies');
    $tree_access_cacheability = new CacheableMetadata();
    $tree_link_cacheability = new CacheableMetadata();
    $items = $this->buildMenuTree($config, $tree_access_cacheability, $tree_link_cacheability);

    // Apply the tree-wide gathered access cacheability metadata and link
    // cacheability metadata to the render array. This ensures that the
    // rendered menu is varied by the cache contexts that the access results
    // and (dynamic) links depended upon, and invalidated by the cache tags
    // that may change the values of the access results and links.
    $tree_cacheability = $tree_access_cacheability->merge($tree_link_cacheability);
    $tree_cacheability->applyTo($element);

    // Build the elements.
    $element['#config'] = $config;
    $element['#items'] = $items;
    $element['#cache']['tags'][] = 'config:ultimenu.' . $config['menu_name'];

    // Attach the Ultimenu assets.
    $element['#attached']['library'][] = 'ultimenu/ultimenu';
    if (!empty($config['skin_basename'])) {
      $element['#attached']['library'][] = 'ultimenu/skin.' . $config['skin_basename'];
    }
    if (!empty($config['orientation']) && strpos($config['orientation'], 'v') !== FALSE) {
      $element['#attached']['library'][] = 'ultimenu/vertical';
    }
    if (!empty($config['ajaxify'])) {
      $element['#attached']['library'][] = 'ultimenu/ajax';
    }
    if (empty($goodies['no-extras'])) {
      $element['#attached']['library'][] = 'ultimenu/extras';
    }

    unset($build, $element['#build']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMenuTree(array $config, CacheableMetadata &$tree_access_cacheability, CacheableMetadata &$tree_link_cacheability) {
    $menu_name = $config['menu_name'];
    $active_trails = $this->tree->getMenuActiveTrail()->getActiveTrailIds($menu_name);
    $tree = $this->tree->loadMenuTree($menu_name);

    if (empty($tree)) {
      return [];
    }

    $ultimenu = [];
    foreach ($tree as $data) {
      // Generally we only deal with visible links, but just in case.
      if (!$data->link->isEnabled()) {
        continue;
      }

      if ($data->access !== NULL && !$data->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Gather the access cacheability of every item in the menu link tree,
      // including inaccessible items. This allows us to render cache the menu
      // tree, yet still automatically vary the rendered menu by the same cache
      // contexts that the access results vary by.
      // However, if $data->access is not an AccessResultInterface object, this
      // will still render the menu link, because this method does not want to
      // require access checking to be able to render a menu tree.
      if ($data->access instanceof AccessResultInterface) {
        $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($data->access));
      }

      // Gather the cacheability of every item in the menu link tree. Some links
      // may be dynamic: they may have a dynamic text (e.g. a "Hi, <user>" link
      // text, which would vary by 'user' cache context), or a dynamic route
      // name or route parameters.
      $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($data->link));

      // Only render accessible links.
      if ($data->access instanceof AccessResultInterface && !$data->access->isAllowed()) {
        continue;
      }

      $ultimenu[$data->link->getPluginId()] = $this->buildMenuItem($data, $active_trails, $config);
    }
    return $ultimenu;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMenuItem($data, array $active_trails, array $config) {
    $goodies    = $this->getSetting('goodies');
    $link       = $data->link;
    $url        = $link->getUrlObject();
    $mlid       = $link->getPluginId();
    $titles     = $this->tool->extractTitleHtml($link);
    $title      = $titles['title'];
    $title_html = $titles['title_html'];
    $li_classes = $li_attributes = $li_options = [];
    $flyout     = '';

    // Must run after the title, modified, or not, the region depends on it.
    $region = $this->tool->getRegionKey($link);
    $config['has_submenu'] = !empty($config['submenu']) && $link->isExpanded() && $data->hasChildren;
    $config['is_ajax_region'] = FALSE;
    $config['is_active'] = array_key_exists($mlid, $active_trails);
    $config['title'] = $title;
    $config['mlid'] = $mlid;
    $li_options['title-class'] = $title;
    $li_options['mlid-hash-class'] = $this->tool->getShortenedHash($mlid);

    if (!empty($goodies['mlid-class'])) {
      $li_options['mlid-class'] = $link->getRouteName() == '<front>' ? 'front_page' : $this->tool->getShortenedUuid($mlid);
    }

    $link_options = $link->getOptions();
    if ($url->isRouted()) {
      if ($config['is_active']) {
        $li_classes[] = 'is-active-trail';
      }

      // Front page has no active trail.
      if ($link->getRouteName() == '<front>') {
        // Intentionally on the second line to not hit it till required.
        if ($this->tool->getPathMatcher()->isFrontPage()) {
          $li_classes[] = 'is-active-trail';
        }
      }

      // Also enable set_active_class for the contained link.
      $link_options['set_active_class'] = TRUE;

      // Add a "data-drupal-link-system-path" attribute to let the
      // drupal.active-link library know the path in a standardized manner.
      // Special case for the front page.
      $system_path = $url->getInternalPath();
      $system_path = $system_path == '' ? '<front>' : $system_path;

      // @todo System path is deprecated - use the route name and parameters.
      $link_options['attributes']['data-drupal-link-system-path'] = $system_path;
      $config['system_path'] = $system_path;
    }

    // Remove browser tooltip if so configured.
    if (!empty($goodies['no-tooltip'])) {
      $link_options['attributes']['title'] = '';
    }

    // Add LI title class based on title if so configured.
    foreach ($li_options as $li_key => $li_value) {
      if (!empty($goodies[$li_key])) {
        $li_classes[] = Html::cleanCssIdentifier(mb_strtolower('uitem--' . str_replace('_', '-', $li_value)));
      }
    }

    // Add hint for external link.
    if ($url->isExternal()) {
      $link_options['attributes']['class'][] = 'is-external';
    }

    // Add LI counter class based on counter if so configured.
    if (!empty($goodies['counter-class'])) {
      static $item_id = 0;
      $li_classes[] = 'uitem--' . (++$item_id);
    }

    // Handle list item class attributes.
    $li_attributes['class'] = array_merge(['ultimenu__item', 'uitem'], $li_classes);

    // Flyout.
    $flyout = $this->getFlyout($region, $config);

    // Provides hints for AJAX.
    $flyout_attributes = [];
    if (!empty($flyout)) {
      if ($config['is_ajax_region']) {
        $flyout_attributes['data-ultiajax-region'] = $region;
        $link_options['attributes']['data-ultiajax-trigger'] = TRUE;
      }
      $title_html .= '<span class="caret" area-hidden="true"></span>';
    }

    $extra_classes = isset($link_options['attributes']['class']) ? $link_options['attributes']['class'] : [];
    $link_options['attributes']['class'] = $extra_classes ? array_merge(['ultimenu__link'], $extra_classes) : ['ultimenu__link'];

    $link_element = [
      '#type' => 'link',
      '#options' => $link_options,
      '#url' => $url,
      '#title' => [
        '#markup' => $title_html,
        '#allowed_tags' => ['b', 'em', 'i', 'small', 'span', 'strong'],
      ],
    ];

    // Pass link to template.
    return [
      'link' => $link_element,
      'flyout' => $flyout,
      'attributes' => new Attribute($li_attributes),
      'flyout_attributes' => new Attribute($flyout_attributes),
      'config' => $config,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildAjaxLink(array $config = []) {
    return [
      '#type' => 'link',
      '#title' => strip_tags($this->getFallbackText()),
      '#attributes' => [
        'class' => [
          'ultimenu__ajax',
          'use-ajax',
        ],
        'rel' => 'nofollow',
        'id' => Html::getUniqueId('ultiajax-' . $this->tool->getShortenedHash($config['mlid'])),
      ],
      '#url' => Url::fromRoute('ultimenu.ajax', [
        'mlid' => $config['mlid'],
        // @todo revert if any issue: 'cur' => $config['current_path'],
        'sub' => $config['has_submenu'] ? 1 : 0,
      ]),
    ];
  }

  /**
   * Return the fallback text.
   */
  public function getFallbackText() {
    return $this->t('@text', ['@text' => $this->getSetting('fallback_text') ?: 'Loading... Click here if it takes longer.']);
  }

  /**
   * Returns the flyout if available.
   */
  public function getFlyout($region, array &$config) {
    $flyout = [];
    if ($regions = $this->getSetting('regions')) {
      if (!empty($regions[$region])) {

        // Simply display the flyout, if AJAX is disabled.
        if (empty($config['ajaxify'])) {
          $flyout = $this->buildFlyout($region, $config);
        }
        else {
          // We have a mix of (non-)ajaxified regions here.
          // Provides an AJAX link as a fallback and also the trigger.
          // No need to check whether the region is empty, or not, as otherwise
          // defeating the purpose of ajaxified regions, to gain performance.
          // The site builder should at least provide one accessible block
          // regardless of complex visibility by paths or roles. A trade off.
          $ajax_regions = isset($config['regions']) ? array_filter($config['regions']) : [];
          $config['is_ajax_region'] = $ajax_regions && in_array($region, $ajax_regions);
          $flyout = $config['is_ajax_region'] ? $this->buildAjaxLink($config) : $this->buildFlyout($region, $config);
        }
      }
    }
    return $flyout;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFlyout($region, array $config) {
    $build   = $content = [];
    $reverse = FALSE;

    if (!empty($config['has_submenu'])) {
      $reverse = !empty($config['submenu_position']) && $config['submenu_position'] == 'bottom';
      $content[] = $this->tree->loadSubMenuTree($config['menu_name'], $config['mlid'], $config['title']);
    }

    if ($blocks = $this->getBlocksByRegion($region, $config)) {
      $content[] = $blocks;
    }

    if ($content = array_filter($content)) {
      $build['content'] = $reverse ? array_reverse($content, TRUE) : $content;
      $build['#config'] = $config;
      $build['#region'] = $region;
      $build['#sorted'] = TRUE;

      // Add the region theme wrapper for the Ultimenu flyout.
      $build['#theme_wrappers'][] = 'region';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksByRegion($region, array $config) {
    if (!isset($this->blocks[$region])) {
      $build = [];
      $blocks = $this->entityTypeManager->getStorage('block')->loadByProperties([
        'theme' => $this->getThemeDefault(),
        'region' => $region,
      ]);

      if ($blocks) {
        uasort($blocks, 'Drupal\block\Entity\Block::sort');

        // Only provides extra access checks if the region is ajaxified.
        if (empty($config['ajaxify'])) {
          foreach ($blocks as $key => $block) {
            if ($block->access('view')) {
              $build[$key] = $this->entityTypeManager->getViewBuilder($block->getEntityTypeId())->view($block, 'block');
            }
          }
        }
        else {
          foreach ($blocks as $key => $block) {
            if ($this->tool->isAllowedBlock($block, $config)) {
              $build[$key] = $this->entityTypeManager->getViewBuilder($block->getEntityTypeId())->view($block, 'block');
            }
          }
        }
      }
      $this->blocks[$region] = $build;
    }
    return $this->blocks[$region];
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    if (!isset($this->regions)) {
      $blocks      = $this->getSetting('blocks');
      $menu_blocks = is_array($blocks) ? array_filter($blocks) : [$blocks];
      $menus       = [];

      foreach ($menu_blocks as $delta => $title) {
        $menus[$delta] = $this->tree->loadMenuTree($delta);
      }

      $regions = [];
      foreach ($menus as $menu_name => $tree) {
        foreach ($tree as $item) {
          $name_id = $this->tool->truncateRegionKey($menu_name);
          $name_id_nice = str_replace("_", " ", $name_id);
          $link = $item->link;

          $menu_title = $this->tool->getTitle($link);
          $region_key = $this->tool->getRegionKey($link);
          $regions[$region_key] = "Ultimenu:$name_id_nice: $menu_title";
        }
      }
      $this->regions = $regions;
    }
    return $this->regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledRegions() {
    if (!isset($this->enabledRegions)) {
      $this->enabledRegions = [];
      $regions_all = $this->getRegions();

      // First limit to enabled regions from the settings.
      if (($regions_enabled = $this->getSetting('regions')) !== NULL) {
        foreach (array_filter($regions_enabled) as $enabled) {
          // We must depend on enabled menu items as always.
          // A disabled menu item will automatically drop its Ultimenu region.
          if (array_key_exists($enabled, $regions_all)) {
            $this->enabledRegions[$enabled] = $regions_all[$enabled];
          }
        }
      }
    }
    return $this->enabledRegions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionsByMenu($menu_name) {
    if (!isset($this->regionsByMenu[$menu_name])) {
      $regions = [];
      foreach ($this->getEnabledRegions() as $key => $region_name) {
        if (strpos($key, 'ultimenu_' . $menu_name . '_') === FALSE) {
          continue;
        }
        $regions[$key] = $region_name;
      }
      $this->regionsByMenu[$menu_name] = $regions;
    }
    return $this->regionsByMenu[$menu_name];
  }

  /**
   * {@inheritdoc}
   */
  public function removeRegions() {
    $goodies = $this->getSetting('goodies');
    if (empty($goodies['force-remove-region'])) {
      return FALSE;
    }
    return $this->tool->parseThemeInfo($this->getRegions());
  }

}
