<?php

namespace Drupal\cheeseburger_menu\Plugin\Block;

/**
 * @file
 * Cheeseburger class extends BlockBase.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\breakpoint\BreakpointManager;
use Drupal\cheeseburger_menu\Controller\RenderCheeseburgerMenuBlock;
use Drupal\Core\Database\Connection;

/**
 * Block info.
 *
 * @Block(
 *   id = "cheesebuger_menu_block",
 *   admin_label = @Translation("Cheeseburger Menu"),
 *   category = @Translation("Block"),
 *   description = @Translation("Provide cheesebugermenu block")
 * )
 */
class CheeseburgerMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * Cheesebuger menu serice.
   *
   * @var \Drupal\cheeseburger_menu\Controller\RenderCheeseburgerMenuBlock
   */
  protected $renderCheesebugerMenuService;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CheeseburgerMenu constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityFieldManagerInterface $entity_field_manager,
                              EntityTypeManagerInterface $entity_type_manager,
                              ModuleHandler $moduleHandler,
                              LanguageManager $languageManager,
                              RouteMatchInterface $route_match,
                              Renderer $renderer,
                              MenuLinkTree $menuLinkTree,
                              ThemeHandler $themeHandler,
                              BreakpointManager $breakpointManager,
                              RenderCheeseburgerMenuBlock $renderCheeseburgerMenuBlock,
                              Connection $connection) {
    parent::__construct($configuration,
      $plugin_id,
      $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->menuTree = $menuLinkTree;
    $this->themeHandler = $themeHandler;
    $this->breakPointManager = $breakpointManager;
    $this->renderCheesebugerMenuService = $renderCheeseburgerMenuBlock;
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('renderer'),
      $container->get('menu.link_tree'),
      $container->get('theme_handler'),
      $container->get('breakpoint.manager'),
      $container->get('render_cheeseburger_menu_lock.service'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'css_default' => FALSE,
        'show_navigation' => TRUE,
        'header_height' => FALSE,
        'menu' => [],
        'taxonomy_vocabulary' => [],
        'phone' => [
          'show' => 0,
          'menu_weight' => '0',
          'store' => '0',
          'manual_title' => '',
        ],
        'lang_switcher' => [
          'show' => FALSE,
          'menu_weight' => 0,
        ],
        'cart' => [
          'show' => 0,
          'menu_weight' => '0',
        ],
        'breakpoints' => ['all'],
        'active_state_enable' => 0,
      ] + parent::defaultConfiguration();
  }



  /**
   * Block form.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $menu_names = $this->renderCheesebugerMenuService->getAllMenuLinkNames();
    $taxonomy_term_names = $this->renderCheesebugerMenuService->getAllTaxonomyTermNames();

    // CSS DEFAULT.
    $form['css_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use default css'),
      '#default_value' => (int) $config['css_default'],
    ];
    // SHOW NAVIGATION.
    $form['show_navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show navigation'),
      '#default_value' => (int) $config['show_navigation'],
    ];
    // ACTIVE STATE ENABLE.
    $form['active_state_enable'] = [
      '#title' => $this->t('Active state enable'),
      '#type' => 'checkbox',
      '#default_value' => (int) $config['active_state_enable'],
      '#description' => $this->t('Cheeseburger menu will try to activate active menu item based on current route, in some cases Cheeseburger is not able to do so. This feature needs to disable caching Drupal Caching system. In case this feature is not needed, disable this to speed up Cheeseburger menu.'),
    ];
    // HEADER HEIGHT.
    $form['header_height'] = [
      '#title' => $this->t('Site header height'),
      '#type' => 'number',
      '#default_value' => (int) $config['header_height'],
    ];

    // LANGUAGE SWITCHER.
    if ($this->languageManager->isMultilingual()) {
      $form += $this->buildMenuItemFormElement('lang_switcher', $this->t('Enable language switcher'));
    }

    if ($this->moduleHandler->moduleExists('commerce_cart')) {
      $form += $this->buildMenuItemFormElement('cart', $this->t('Cart'));
    }

    $form += $this->buildMenuItemFormElement('phone', $this->t('Phone'));

    $options[0] = $this->t('Manual');
    if ($this->moduleHandler->moduleExists('commerce_store')) {
      $sql = $this->database->query("SELECT store_id, name FROM commerce_store_field_data")
        ->fetchAll();

      foreach ($sql as $stores) {
        $options[$stores->store_id] = $stores->name;
      }
    }
    // TODO CHECK IF STORE HAS field_phone.
    $form['phone']['store'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose your store'),
      '#options' => $options,
      '#states' => [
        'invisible' => [
          ':input[name="settings[phone][show]"]' => ['checked' => FALSE],
        ],
      ],
      '#default_value' => $config['phone']['store'],
    ];
    $form['phone']['manual_title'] = [
      '#title' => 'Phone number',
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="settings[phone][store]"]' => ['value' => 0],
          ':input[name="settings[phone][show]"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $config['phone']['manual_title'],
      '#suffix' => '</div>',
    ];
    $form['phone']['description'] = [
      '#markup' => '<div>' . $this->t('To use phone from store, add field with machine name field_phone to your store type') . '</div>',
    ];
    // MENU.
    $form['menu'] = $this->buildConfigMenuForm($menu_names, 'menu', $this->t('Menus'));
    // TAXONOMY.
    $form['taxonomy_vocabulary'] = $this->buildConfigMenuForm($taxonomy_term_names, 'taxonomy_vocabulary', $this->t('Vocabularies'));
    // TODO BREAKPOINTS.
    $breakpoints = $this->renderCheesebugerMenuService->returnBreakpointsForDefaultTheme();
    $breakpoint_description = $this->t('This module uses breakpoints from your default theme<br>If you want to change it, make your changes in default_theme_name.breakpoints.yml<br><br>');
    if (!empty($breakpoints)) {
      $form['breakpoints'] = [
        '#type' => 'fieldset',
        '#title' => 'Enable breakpoints',
      ];

      $form['breakpoints']['all'] = [
        '#type' => 'select',
        '#options' => [
          0 => 'Custom',
          1 => 'All',
        ],
        '#default_value' => in_array('all', $config['breakpoints']) ? 1 : 0,
      ];

      $options = [
        '0' => '0px',
      ];
      foreach ($breakpoints as $name => $breakpoint) {
        if (strtolower($breakpoint['label']) != 'all' &&
          strpos($breakpoint['mediaQuery'], ' 0px') === FALSE) {
          $options[$name] = $breakpoint['label'];
          $breakpoint_description .= $breakpoint['label'] . ': ' . $breakpoint['mediaQuery'] . '<br>';
        }
      }

      $form['breakpoints']['from'] = [
        '#prefix' => '<div class="container-inline">',
        '#type' => 'select',
        '#states' => [
          'visible' => [
            ':input[name="settings[breakpoints][all]"]' => ['value' => 0],
          ],
        ],
        '#options' => $options,
        '#title' => 'From',
        '#default_value' => array_key_exists('breakpoints', $config) ? array_key_exists('from', $config['breakpoints']) ? $config['breakpoints']['from'] : '0' : '0',
      ];
      $form['breakpoints']['to'] = [
        '#type' => 'select',
        '#suffix' => '</div>',
        '#title' => 'To',
        '#states' => [
          'visible' => [
            ':input[name="settings[breakpoints][all]"]' => ['value' => 0],
          ],
        ],
        '#options' => $options,
        '#default_value' => array_key_exists('breakpoints', $config) ? array_key_exists('to', $config['breakpoints']) ? $config['breakpoints']['to'] : '0' : '0',
      ];
      if (!empty($breakpoint_description)) {
        $form['breakpoints']['#description'] = $breakpoint_description;
      }
    }

    return $form;
  }

  /**
   * Returns default menu form element.
   */
  public function buildMenuItemFormElement($machine_name, $label) {
    $form[$machine_name]['show'] = [
      '#type' => 'checkbox',
      '#prefix' => '<div class="container-inline">',
      '#title' => $label,
      '#default_value' => (int) $this->configuration[$machine_name]['show'],
    ];
    $form[$machine_name]['menu_weight'] = [
      '#type' => 'weight',
      '#default_value' => $this->configuration[$machine_name]['menu_weight'],
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="settings[' . $machine_name . '][show]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Builds menu table.
   */
  public function buildConfigMenuForm($names, $machine_name, $caption) {
    $header = [
      'select' => '',
      'menu' => $this->t('Name'),
      'menu_weight' => $this->t('Weight'),
      'title' => $this->t('Title'),
      'collapsible_title' => $this->t('Collapsible title'),
      'manual_title' => $this->t('Manual title'),
    ];

    $form_part = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $header,
      '#empty' => $this->t('No menus found'),
    ];

    foreach ($names as $id => $name) {
      $form_part[$id] = [
        'select' => [
          '#type' => 'checkbox',
          '#default_value' => array_key_exists($id, $this->configuration[$machine_name]) ? 1 : 0,
        ],
        'menu' => ['#markup' => $name],
        'menu_weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => array_key_exists($id, $this->configuration[$machine_name]) ? $this->configuration[$machine_name][$id]['menu_weight'] : 0,
        ],
        'title' => [
          '#type' => 'select',
          '#options' => [
            'do_not_show' => $this->t('Do not show'),
            'use_default' => $this->t('Use default title'),
            'manual' => $this->t('Manual title'),
          ],
          '#title_display' => 'invisible',
          '#default_value' => array_key_exists($id, $this->configuration[$machine_name]) ? $this->configuration[$machine_name][$id]['title'] : 0,
        ],
        'collapsible_title' => [
          '#type' => 'checkbox',
          '#default_value' => array_key_exists($id, $this->configuration[$machine_name]) ? $this->configuration[$machine_name][$id]['collapsible_title'] : 0,
        ],
        'manual_title' => [
          '#type' => 'textfield',
          '#title_display' => 'invisible',
          '#default_value' => array_key_exists($id, $this->configuration[$machine_name]) ? $this->configuration[$machine_name][$id]['manual_title'] : '',
        ],
      ];
    }

    return $form_part;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    $values = $form_state->getValues();
    if (array_key_exists('breakpoints', $values) && $values['breakpoints']['all'] == '0') {
      $breakpoints = $this->renderCheesebugerMenuService->returnBreakpointsForDefaultTheme();
      $breakpoints_order = ['0'];
      foreach ($breakpoints as $breakpoint_name => $breakpoint) {
        $breakpoints_order[] = $breakpoint_name;
      }
      if (array_search($values['breakpoints']['from'], $breakpoints_order) >= array_search($values['breakpoints']['to'], $breakpoints_order)) {
        $form_state->setErrorByName('from', $this->t('The first breakpoint should be smaller than second!'));
      }
    }
  }

  /**
   * Sends and store the block by collected data.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->configuration = $form_state->getValues();
    $this->unsetNotSelected($this->configuration['menu']);
    $this->unsetNotSelected($this->configuration['taxonomy_vocabulary']);
    $entity = $form_state->getFormObject()->getEntity();
    $this->configuration['block_machine_name'] = $entity->id();
    // TODO Build breakpoints.
    $this->configuration['breakpoints'] = ['all'];
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetNotSelected(&$config_menus) {
    foreach ($config_menus as $id => &$config_menu) {
      if ((bool) $config_menu['select']) {
        unset($config_menu['select']);
      } else {
        unset($config_menus[$id]);
      }
    }
  }

  /**
   * Returns block machine name.
   */
  private function getBlockMachineName() {
    if (isset($this->configuration['block_machine_name'])) {
      return $this->configuration['block_machine_name'];
    }
    $blocks = $this->entityTypeManager->getStorage('block')
      ->loadByProperties(['plugin' => $this->getBaseId()]);
    foreach ($blocks as $block) {
      if ($block->get('settings') == $this->configuration) {
        return $block->getOriginalId();
      }
    }
    return FALSE;
  }

  /**
   * Formats media query.
   */
  public function formatBreakpoints($breakpoints) {
    $breakpoints_theme = $this->renderCheesebugerMenuService->returnBreakpointsForDefaultTheme();
    $media_query = [];
    if ($breakpoints['from'] == '0') {
      $media_query['from'] = '0';
    }
    else {
      $media_query['from'] = $breakpoints_theme[$breakpoints['from']]['mediaQuery'];
    }
    $media_query['to'] = $breakpoints_theme[$breakpoints['to']]['mediaQuery'];
    return $media_query;
  }

  /**
   * Validates all config.
   * @deprecated
   */
  public function validateConfiguration($config) {
    $new_config_elements = [
      'menu',
      'taxonomy_vocabulary',
    ];
    foreach ($new_config_elements as $new_config_element) {
      if (!array_key_exists($new_config_element, $config)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Searches for some old config keys.
   * @deprecated
   */
  public function identifyOldConfig($config) {
    $old_config_elements = [
      'menus_appear',
      'taxonomy_appear',
      'menus_weight',
      'taxonomy_weight',
    ];

    foreach ($old_config_elements as $old_config_element) {
      if (!array_key_exists($old_config_element, $config)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Building block.
   */
  public function build() {
    $config = $this->getConfiguration();
    if (!$this->validateConfiguration($config)) {
      if ($this->identifyOldConfig($config)) {
        $this->messenger->addWarning($this->t('Your current Cheeseburger Menu block configuration is not compatible with the newest release. You can either go edit the block and adjust its settings or, even better, delete the block and place it again.'), 'warning');
        $this->messenger->addWarning($this->t('We are assuming that you have an old version of the Cheeseburger Menu, so take a note that there are some major changes in the new one, like the default CSS with the full design (you can turn it on/off in block settings).'));
      }
      else {
        $this->messenger->addWarning($this->t('Your cheeseburger menu block configuration is not valid, try to save it again in block edit.'), 'warning');
      }
      return ['#cache' => ['max-age' => 0]];
    }

    $url = Url::fromRouteMatch($this->routeMatch)->toString();
    $headerHeight = isset($config['header_height']) ? $config['header_height'] : 0;

    $render[] = [
      '#type' => 'inline_template',
      '#template' => '<div class="cheeseburger-menu__trigger"></div>
        <div class="cheeseburger-menu__wrapper" style="top: ' . $headerHeight . 'px">',
    ];

    $render['#attached']['drupalSettings'] = [
      'headerHeight' => $headerHeight,
      'block_id' => $this->getBlockMachineName(),
    ];

    if (in_array('all', $config['breakpoints'])) {
      $render[] = $this->renderCheesebugerMenuService->renderTree($config, $url);
      $render['#attached']['drupalSettings'] += [
        'instant_show' => TRUE,
        'breakpoints' => [],
        'current_route' => $url,
      ];
    }
    else {
      $render['#attached']['drupalSettings']['block_id'] = $this->getBlockMachineName();

      $render['#attached']['drupalSettings'] += [
        'instant_show' => FALSE,
        'breakpoints' => $this->formatBreakpoints($config['breakpoints']),
        'current_route' => $url,
      ];
    }
    $render[] = [
      '#markup' => '</div>',
    ];
    if ($config['css_default'] == 1) {
      $render['#attached']['library'][] = 'cheeseburger_menu/cheeseburger_menu.css';
    }

    return $render;
  }

}
