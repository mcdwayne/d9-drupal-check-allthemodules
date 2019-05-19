<?php

namespace Drupal\toolshed_menu\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\toolshed_menu\Menu\MenuTreeStorageData;
use Drupal\toolshed_menu\Menu\MenuSelectionTrait;
use Drupal\toolshed\Utility\ArrayUtils;

/**
 * Create a configurable navigation block based on menu tree structures.
 *
 * Navigation blocks that utilize a ToolshedMenuResolver plugin to determine
 * what menu link should be currently active. It will use this menu link as
 * the basis for determining the active trail, and menu root.
 *
 * The block also has configurations for the use of accordions, displaying
 * siblings links, and menu depth.
 *
 * @Block(
 *   id = "toolshed_menu_navigation",
 *   admin_label = @Translation("Toolshed: Menu Navigation Block"),
 * );
 */
class MenuNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use MenuSelectionTrait;

  const RENDER_CHILDREN = 0x01;
  const RENDER_SIBLINGS = 0x02;

  /**
   * The menu resolver specified and loaded for this block instance.
   *
   * @var Drupal\toolshed_menu\MenuResolver\MenuResolverInterface
   */
  private $menuResolver;

  /**
   * The dependency injection container for services to use.
   *
   * Because the block loads different services dependant on if the block
   * is being loaded for configurations or building, the block gets a reference
   * to the container, and requests the services it needs based on its task.
   *
   * @var Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The logging channel for logging info, warnings or errors.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Create a new instance of this navigation menu block.
   */
  public function __construct(array $config, $pluginId, $pluginDef, ContainerInterface $container) {
    parent::__construct($config, $pluginId, $pluginDef);

    $this->container = $container;
    $this->logger = $this->container->get('logger.factory')->get('toolshed_menu');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // A list of menu IDs for menus that should be followed. An empty
      // array means, that all menus are allowed.
      'menus' => [],
      // The starting depth of the menu. 0 means start at the root, while
      // `1` would mean start at second level menu items. Any menu roots
      // found actually take priority over this value. So if this is set at
      // depth `2` but a menu root is found at level `3`, the menu will be
      // rooted at the 3rd level item.
      'min_depth' => 0,
      // The depth of the menu, relative to the root. The 0 means no limit.
      'tree_depth' => 0,
      // Option for determining what portion of the menu tree gets rendered.
      'display_style' => 'active_trail',
      // Display style determines how to render the display.
      // Is a combination of the MenuNavigationBlock render flag constants.
      'display_flags' => self::RENDER_CHILDREN | self::RENDER_SIBLINGS,
      // ID of the menu resolver plugin to use when resolving
      // the menu content link to use.
      'menu_resolver' => 'default_menu_resolver',
    ];
  }

  /**
   * Get the renderable portion of the full active menu trail.
   *
   * This rooted trail includes the parent of root menu
   * link, or the tree root (empty string).Get the portion of the full active menu trail that gets rendered based on
   * the navigation block settings. This takes into account the minimum tree
   * depth or menu roots.
   *
   * @param string $menuName
   *   The machine name of the menu to use.
   * @param array $trail
   *   An array of menu IDs for the active trail.
   *
   * @return string[]
   *   The menu item IDs of the renderable portion of the active trail.
   *   This would be the trail starting at the menu root to the menu tree
   *   depth, or last portion of the active trail.
   */
  protected function getRootedTrail($menuName, array $trail = []) {
    if (!empty($this->configuration['menus'][$menuName])) {
      $roots = array_flip($this->configuration['menus'][$menuName]);
      $values = $trail;

      $rooted = [];
      while ($itemId = array_pop($values)) {
        array_unshift($rooted, $itemId);

        if (isset($roots[$itemId])) {
          $itemId = array_pop($values);
          array_unshift($rooted, isset($itemId) ? $itemId : '');

          return $rooted;
        }
      }
    }

    // If we didn't find a root, return the trail starting at the min depth.
    return $this->configuration['min_depth'] > 0
      ? array_slice($trail, $this->configuration['min_depth'] - 1)
      : array_merge([''], $trail);
  }

  /**
   * Additional render flags for display when not using full menu display.
   *
   * @return array
   *   Additionally available render flags for what to display when
   *   the display style is not "full_menu".
   */
  protected function getRenderOptions() {
    return [
      self::RENDER_CHILDREN => $this->t('Display children'),
      self::RENDER_SIBLINGS => $this->t('Display siblings'),
    ];
  }

  /**
   * Retrieve the menu resolver configured for this block instance.
   *
   * @return Drupal\toolshed_menu\MenuResolver\MenuResolverInterface
   *   Return the menu resolver plugin configured by this navigation block.
   */
  protected function getMenuResolver() {
    if (!isset($this->menuResolver)) {
      try {
        $resolverManager = $this->container->get('plugin.manager.toolshed.menu_resolver');
        $this->menuResolver = $resolverManager->createInstance($this->configuration['menu_resolver']);
      }
      catch (PluginNotFoundException $e) {
        $this->logger->error($this->t('Unable to load menu resolver (:resolver_name) -- :message', [
          ':resolver_name' => $this->configuration['menu_resolver'],
          ':message' => $e->getMessage(),
        ]));

        $this->menuResolver = FALSE;
      }
    }

    return $this->menuResolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $allowedMenus = array_keys($this->configuration['menus']);
    $tags = empty($allowedMenus) ? ['config:system.menu'] : ArrayUtils::prefix($allowedMenus, 'config:system.menu.');

    if ($menuResolver = $this->getMenuResolver()) {
      $tags = Cache::mergeTags($tags, $menuResolver->getCacheTags($allowedMenus));
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    if ($menuResolver = $this->getMenuResolver()) {
      $allowedMenus = array_keys($this->configuration['menus']);
      return Cache::mergeContexts(parent::getCacheContexts(), $menuResolver->getCacheContexts($allowedMenus));
    }
    else {
      return parent::getCacheContexts();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $state) {
    $form = parent::blockForm($form, $state);

    // Options for how to render (and what parts of) the tree.
    $form['display_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Render menu style'),
      '#options' => [],
      '#default_value' => $this->configuration['display_style'],
    ];

    foreach ($this->getDisplayStyles() as $styleKey => $styleConf) {
      $form['display_style']['#options'][$styleKey] = $styleConf['label'];
    }

    $renderFlags = [];
    $renderOpts = $this->getRenderOptions();
    foreach ($renderOpts as $renderFlag => $renderOptLabel) {
      if ($renderFlag & $this->configuration['display_flags']) {
        $renderFlags[$renderFlag] = $renderFlag;
      }
    }
    $form['display_flags_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          'select[name="settings[display_style]"]' => ['value' => 'active_trail'],
        ],
      ],

      'display_flags' => [
        '#type' => 'checkboxes',
        '#options' => $renderOpts,
        '#default_value' => $renderFlags,
      ],
    ];

    $form['min_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum depth'),
      '#min' => 0,
      '#size' => 3,
      '#default_value' => $this->configuration['min_depth'],
      '#description' => $this->t('The menu depth to start building the tree from. The value "0" means to start at the root of the menu. Any found menu roots take priority over this value.'),
    ];

    $form['tree_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Max height of rendered tree'),
      '#min' => 0,
      '#max' => MenuTreeStorage::MAX_DEPTH,
      '#size' => 3,
      '#default_value' => $this->configuration['tree_depth'],
      '#description' => $this->t('The height of the rendered tree, starting from the discovered root of the tree (min depth or matching menu roots).'),
    ];

    // Allow the end users to select which plugin should be used for
    // determining which menu link to use as the starting point for
    // where to start building the navigation menu.
    $resolverOpts = [];
    $resolverManager = $this->container->get('plugin.manager.toolshed.menu_resolver');

    foreach ($resolverManager->getDefinitions() as $resolverId => $resolverInfo) {
      $resolverOpts[$resolverId] = $resolverInfo['label'];
    }

    $form['menu_resolver'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu resolver'),
      '#required' => TRUE,
      '#options' => $resolverOpts,
      '#default_value' => $this->configuration['menu_resolver'],
      '#description' => $this->t('Plugin used to determine which menu content link is currently active and determines what portion of the menu tree is active.'),
    ];

    // Create the table for managing and ordering various menus.
    $form['menu-settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Menu Priority & Roots'),
      '#description' => $this->t('Order the menus so that the top-most menu in the table is the highest priority menu to render. The ordering, and menu roots settings are only saved for enabled menus.'),
      '#tree' => TRUE,

      'menus' => [
        '#type' => 'table',
        '#attributes' => ['id' => 'menu-settings-table'],
        '#header' => [
          $this->t('Menu Name'),
          $this->t('Enabled'),
          $this->t('Tree Roots'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          'options' => [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'menu-sort-weight',
          ],
        ],
      ],
    ];

    $menuSettings = $this->configuration['menus'];
    $loadedMenus = $this->getAvailableMenus();
    $disabledMenus = array_diff_key($loadedMenus, $menuSettings);
    $availMenus = $menuSettings + array_fill_keys(array_keys($disabledMenus), []);

    $weight = 0;
    foreach ($availMenus as $menuId => $roots) {
      if (!isset($loadedMenus[$menuId])) {
        continue;
      }

      $form['menu-settings']['menus'][$menuId] = [
        '#attributes' => [
          'id' => "menu-${menuId}",
          'class' => ['draggable'],
        ],

        'menu_name' => ['#plain_text' => $loadedMenus[$menuId]->label()],
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable menu'),
          '#title_display' => 'invisible',
          '#default_value' => isset($menuSettings[$menuId]),
        ],
        'menu_roots' => [
          '#type' => 'select',
          '#title' => $this->t('Menu tree roots'),
          '#title_display' => 'invisible',
          '#options' => $this->getMenuRootOptions($menuId, $loadedMenus[$menuId]->label()),
          '#default_value' => $roots,
          '#multiple' => TRUE,
        ],
        'weight' => [
          '#type' => 'number',
          '#title' => $this->t('Menu priority order'),
          '#title_display' => 'invisible',
          '#default_value' => ++$weight,
          '#attributes' => ['class' => ['menu-sort-weight']],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $state) {
    parent::blockSubmit($form, $state);

    $values = $state->getValues();

    // Update the block configurations based on the user submissions.
    $this->configuration['menu_resolver'] = $values['menu_resolver'];

    // Update settings pertaining to how the menu is rendered.
    $this->configuration['display_style'] = $values['display_style'];
    $this->configuration['min_depth'] = $values['min_depth'];
    $this->configuration['tree_depth'] = $values['tree_depth'];

    $this->configuration['display_flags'] = 0;
    foreach (array_filter($values['display_flags_wrapper']['display_flags']) as $value) {
      $this->configuration['display_flags'] |= $value;
    }

    // Only capture menu settings from enabled menus. Menus are captured in
    // priority order, where the highest priority menus are captured first.
    $menus = [];
    foreach ($values['menu-settings']['menus'] as $menuId => $menuInfo) {
      if (!empty($menuInfo['enabled'])) {
        $menus[$menuId] = array_filter($menuInfo['menu_roots']);
      }
    }
    $this->configuration['menus'] = $menus;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menuResolver = $this->getMenuResolver();
    $allowedMenus = array_keys($this->configuration['menus']);

    if ($menuResolver && ($menuLink = $menuResolver->resolve($allowedMenus))) {
      try {
        $menuTreeData = MenuTreeStorageData::create($this->container);
        $activeTrail = $menuTreeData->getIdsByMlid($menuLink->parents);

        if (!empty($menuLink->mlid)) {
          $activeTrail[$menuLink->mlid] = $menuLink->id;
        }
        $rootedTrail = $this->getRootedTrail($menuLink->menu_name, $activeTrail);
        $trailDepth = count($activeTrail) - count($rootedTrail) + 1;

        // Adjust the menu tree loading and display based on render settings.
        $displayStyles = $this->getDisplayStyles();
        $style = $this->configuration['display_style'];

        if (isset($displayStyles[$style]) && method_exists($this, $displayStyles[$style]['callback'])) {
          $styleCallback = $displayStyles[$style]['callback'];
          $tree = $this->{$styleCallback}($menuLink->menu_name, $rootedTrail, $trailDepth);
        }
        else {
          return;
        }

        $menuTreeManager = $this->container->get('menu.link_tree');
        $tree = $menuTreeManager->transform($tree, [
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ]);

        return $menuTreeManager->build($tree);
      }
      catch (Exception $e) {
        $this->logger->error($this->t('Build menu :type :message', [
          ':type' => get_class($e),
          ':message' => $e->getMessage(),
        ]));
      }
    }
  }

  /**
   * Return a list of available display styles for fetching the menu tree.
   *
   * @return array
   *   An array keyed by the option machine name for a menu tree style. Each
   *   value should be an array with the label, and method callback to use
   *   to generate a menu tree compatible the MenuTreeManager::build() method.
   */
  public function getDisplayStyles() {
    return [
      'active_trail' => [
        'label' => $this->t('Show trail to root'),
        'callback' => 'fetchMenuTreeActiveTrail',
      ],
      'sibling_trail' => [
        'label' => $this->t('Show trail with siblings'),
        'callback' => 'fetchMenuTreeSiblingTrail',
      ],
      'full_menu' => [
        'label' => $this->t('Show full menu'),
        'callback' => 'fetchMenuTreeFullMenu',
      ],
    ];
  }

  /**
   * Fetch the full tree of menu links from the root to the configured height.
   *
   * @param string $menuName
   *   The machine name of the menu to fetch the links from.
   * @param string[] $rootedTrail
   *   The active menu trail (menu link IDs) starting from the tree root to
   *   display to.
   * @param int $trailDepth
   *   The rooted trail might not be at the root of the menu tree, this is the
   *   depth of root ID from the $rootedTrail parameter.
   *
   * @return Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array of tree link elements to render for the menu blocks.
   */
  protected function fetchMenuTreeFullMenu($menuName, array $rootedTrail, $trailDepth) {
    $maxMenuDepth = empty($this->configuration['tree_depth'])
      ? MenuTreeStorage::MAX_DEPTH : ($trailDepth + $this->configuration['tree_depth']);

    $menuParams = new MenuTreeParameters();
    $menuParams->excludeRoot();
    $menuParams->onlyEnabledLinks();
    $menuParams->setMaxDepth($maxMenuDepth);

    if (!empty($rootedTrail)) {
      $menuParams->setRoot(reset($rootedTrail));
      $menuParams->setActiveTrail($rootedTrail);
    }

    return $this->container->get('menu.link_tree')->load($menuName, $menuParams);
  }

  /**
   * Build the menu tree only from items in the trail, siblings and children.
   *
   * Build the menu tree displaying the $rootedTrail, and siblings and/or
   * children menu links depending on the configured menu settings.
   *
   * @param string $menuName
   *   The machine name of the menu to fetch the links from.
   * @param string[] $rootedTrail
   *   The active menu trail (menu link IDs) starting from the tree root to
   *   display to.
   * @param int $trailDepth
   *   The rooted trail might not be at the root of the menu tree, this is the
   *   depth of root ID from the $rootedTrail parameter.
   *
   * @return Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array of tree link elements to render for the menu blocks.
   */
  protected function fetchMenuTreeActiveTrail($menuName, array $rootedTrail, $trailDepth) {
    $menuLinkManager = $this->container->get('plugin.manager.menu.link');

    $renderTrail = ($this->configuration['tree_depth'] > 0)
      ? array_slice($rootedTrail, 0, $this->configuration['tree_depth']) : $rootedTrail;

    if ($this->configuration['display_flags']) {
      $parents = [];

      if ($this->configuration['display_flags'] & self::RENDER_CHILDREN) {
        $parents[] = end($renderTrail);
      }
      if ($this->configuration['display_flags'] & self::RENDER_SIBLINGS) {
        $last = array_pop($renderTrail);
        $parents[] = empty($renderTrail) ? $menuLinkManager->createInstance($last)->getParent() : end($renderTrail);
      }

      $menuParams = new MenuTreeParameters();
      $menuParams->excludeRoot();
      $menuParams->onlyEnabledLinks();
      $menuParams->setActiveTrail($rootedTrail);
      $menuParams->setRoot(empty($renderTrail) ? end($parents) : end($renderTrail));
      $menuParams->addExpandedParents($parents);
      $subtree = $this->container->get('menu.link_tree')->load($menuName, $menuParams);
    }
    else {
      $subtree = [];
    }

    $depth = $trailDepth + count($renderTrail);
    if (!$this->configuration['exclude_root']) {
      array_shift($renderTrail);
    }

    while ($linkId = array_pop($renderTrail)) {
      $menuLink = $menuLinkManager->createInstance($linkId);
      $subtree = [
        $linkId => new MenuLinkTreeElement($menuLink, !empty($subtree), $depth--, TRUE, $subtree),
      ];
    }

    return $subtree;
  }

  /**
   * Build the menu tree from the root to active item with sibling of the trail.
   *
   * Build the menu tree displaying the $rootedTrail, and siblings of all the
   * menu links of the entire trail.
   *
   * @param string $menuName
   *   The machine name of the menu to fetch the links from.
   * @param string[] $rootedTrail
   *   The active menu trail (menu link IDs) starting from the tree root to
   *   display to.
   * @param int $trailDepth
   *   The rooted trail might not be at the root of the menu tree, this is the
   *   depth of root ID from the $rootedTrail parameter.
   *
   * @return Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array of tree link elements to render for the menu blocks.
   */
  protected function fetchMenuTreeSiblingTrail($menuName, array $rootedTrail, $trailDepth) {
    $maxMenuDepth = empty($this->configuration['tree_depth'])
      ? MenuTreeStorage::MAX_DEPTH : ($trailDepth + $this->configuration['tree_depth']);

    $menuParams = new MenuTreeParameters();
    $menuParams->excludeRoot();
    $menuParams->onlyEnabledLinks();
    $menuParams->setMaxDepth($maxMenuDepth);

    if (!empty($rootedTrail)) {
      $menuParams->setRoot(reset($rootedTrail));
      $menuParams->setActiveTrail($rootedTrail);
      $menuParams->addExpandedParents($rootedTrail);
    }

    return $this->container->get('menu.link_tree')->load($menuName, $menuParams);
  }

}
