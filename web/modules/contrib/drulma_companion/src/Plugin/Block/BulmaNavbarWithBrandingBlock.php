<?php

namespace Drupal\drulma_companion\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a bulma navbar with branding block.
 *
 * @Block(
 *   id = "drulma_companion_bulma_navbar_with_branding",
 *   admin_label = @Translation("Bulma navbar with branding"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock",
 *   category = @Translation("Bulma navbar")
 * )
 */
class BulmaNavbarWithBrandingBlock extends SystemMenuBlock {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * Constructs a new BulmaNavbarWithBrandingBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_storage
   *   The menu storage.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    MenuLinkTreeInterface $menu_tree,
    ConfigFactoryInterface $config_factory,
    EntityStorageInterface $menu_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $menu_tree);
    $this->configFactory = $config_factory;
    $this->menuStorage = $menu_storage;
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
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'navbar_color' => '',
      'horizontally_centered' => TRUE,
      'navbar_start_display' => TRUE,
      'end_menu' => '',
      'end_menu_level' => 1,
      'end_menu_depth' => 0,
      'use_site_logo' => TRUE,
      'use_site_name' => TRUE,
      'use_site_slogan' => TRUE,
      'site_name_size' => '4',
      'site_slogan_size' => '6',
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['navbar_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Navbar color'),
      '#default_value' => $this->configuration['navbar_color'],
      '#description' => $this->t('Colors according to the <a href="@url">Bulma navbar options</a>', [
        '@url' => 'https://bulma.io/documentation/components/navbar/#colors',
      ]),
      '#options' => [
        '' => $this->t('Default'),
        'primary' => $this->t('Primary'),
        'link' => $this->t('Link'),
        'info' => $this->t('Info'),
        'success' => $this->t('Success'),
        'warning' => $this->t('Warning'),
        'danger' => $this->t('Danger'),
        'black' => $this->t('Black'),
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
        'white' => $this->t('White'),
      ],
    ];

    $form['horizontally_centered'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Center navbar horizontally'),
      '#default_value' => $this->configuration['horizontally_centered'],
      '#description' => $this->t('Use a .container for the navbar content. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/layout/container/',
      ]),
    ];

    // Since this block is a merge between a branding and a menu and we inherit
    // from the menu block the following form is copied from that block plugin.
    // Get the theme.
    $theme = $form_state->get('block_theme');

    // Get permissions.
    $url_system_theme_settings = new Url('system.theme_settings');
    $url_system_theme_settings_theme = new Url('system.theme_settings_theme', ['theme' => $theme]);

    if ($url_system_theme_settings->access() && $url_system_theme_settings_theme->access()) {
      // Provide links to the Appearance Settings and Theme Settings pages
      // if the user has access to administer themes.
      $site_logo_description = $this->t('Defined on the <a href=":appearance">Appearance Settings</a> or <a href=":theme">Theme Settings</a> page.', [
        ':appearance' => $url_system_theme_settings->toString(),
        ':theme' => $url_system_theme_settings_theme->toString(),
      ]);
    }
    else {
      // Explain that the user does not have access to the Appearance and Theme
      // Settings pages.
      $site_logo_description = $this->t('Defined on the Appearance or Theme Settings page. You do not have the appropriate permissions to change the site logo.');
    }
    $url_system_site_information_settings = new Url('system.site_information_settings');
    if ($url_system_site_information_settings->access()) {
      // Get paths to settings pages.
      $site_information_url = $url_system_site_information_settings->toString();

      // Provide link to Site Information page if the user has access to
      // administer site configuration.
      $site_name_description = $this->t('Defined on the <a href=":information">Site Information</a> page.', [':information' => $site_information_url]);
      $site_slogan_description = $this->t('Defined on the <a href=":information">Site Information</a> page.', [':information' => $site_information_url]);
    }
    else {
      // Explain that the user does not have access to the Site Information
      // page.
      $site_name_description = $this->t('Defined on the Site Information page. You do not have the appropriate permissions to change the site logo.');
      $site_slogan_description = $this->t('Defined on the Site Information page. You do not have the appropriate permissions to change the site logo.');
    }

    $form['block_branding'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Toggle branding elements'),
      '#description' => $this->t('Choose which branding elements you want to show in this block instance.'),
    ];
    $form['block_branding']['use_site_logo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site logo'),
      '#description' => $site_logo_description,
      '#default_value' => $this->configuration['use_site_logo'],
    ];

    $form['block_branding']['use_site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site name'),
      '#description' => $site_name_description,
      '#default_value' => $this->configuration['use_site_name'],
    ];
    $form['block_branding']['use_site_slogan'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site slogan'),
      '#description' => $site_slogan_description,
      '#default_value' => $this->configuration['use_site_slogan'],
    ];

    // End of the copied branding form.
    // Defines the available bulma sizes.
    $validSizes = range(1, 7);
    $sizeOptions = array_combine($validSizes, $validSizes);
    $sizeDescription = $this->t('Sizes according to the <a href="@url">Bulma typography helpers</a>', [
      '@url' => 'https://bulma.io/documentation/modifiers/typography-helpers/',
    ]);

    $form['block_branding']['site_name_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Site name size'),
      '#default_value' => $this->configuration['site_name_size'],
      '#description' => $sizeDescription,
      '#options' => $sizeOptions,
    ];
    $form['block_branding']['site_slogan_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Site slogan size'),
      '#description' => $sizeDescription,
      '#default_value' => $this->configuration['site_slogan_size'],
      '#options' => $sizeOptions,
    ];

    $form['navbar_start_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display menu at the start of the navbar (after the branding)'),
      '#description' => $this->t('This may be useful to disable when only the menu at the end of the navbar is needed'),
      '#default_value' => $this->configuration['navbar_start_display'],
    ];

    $endMenuOptions = [
      '' => ' - ' . $this->t('No menu') . ' - ',
    ];
    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      $endMenuOptions[$menu] = $entity->label();
    }
    $form['end_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Display another menu at the end of the navbar'),
      '#default_value' => $this->configuration['end_menu'],
      '#options' => $endMenuOptions,
    ];
    $form += parent::blockForm($form, $form_state);

    $form['menu_levels']['end_menu_level'] = $form['menu_levels']['level'];
    $form['menu_levels']['end_menu_depth'] = $form['menu_levels']['depth'];
    $form['menu_levels']['level']['#title'] = $this->t('Initial visibility level for the menu at the start of the navbar');
    $form['menu_levels']['depth']['#title'] = $this->t('Numbers of levels to display for the menu at the start of the navbar');
    $form['menu_levels']['end_menu_level']['#title'] = $this->t('Initial visibility level for the menu at the end of the navbar');
    $form['menu_levels']['end_menu_depth']['#title'] = $this->t('Numbers of levels to display  for the menu at the end of the navbar');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['navbar_color'] = $form_state->getValue('navbar_color');
    $this->configuration['horizontally_centered'] = $form_state->getValue('horizontally_centered');
    $this->configuration['navbar_start_display'] = $form_state->getValue('navbar_start_display');

    $block_branding = $form_state->getValue('block_branding');
    $this->configuration['use_site_logo'] = $block_branding['use_site_logo'];
    $this->configuration['use_site_name'] = $block_branding['use_site_name'];
    $this->configuration['use_site_slogan'] = $block_branding['use_site_slogan'];
    $this->configuration['site_name_size'] = $block_branding['site_name_size'];
    $this->configuration['site_slogan_size'] = $block_branding['site_slogan_size'];

    $this->configuration['end_menu'] = $form_state->getValue('end_menu');
    $this->configuration['end_menu_level'] = $form_state->getValue('end_menu_level');
    $this->configuration['end_menu_depth'] = $form_state->getValue('end_menu_depth');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $site_config = $this->configFactory->get('system.site');

    $build['navbar_start'] = parent::build();
    $build['navbar_start']['#access'] = $this->configuration['navbar_start_display'];
    if (isset($build['navbar_start']['#theme'])) {
      $build['navbar_start']['#theme'] = $this->addNavbarSuggestion($build['navbar_start']['#theme']);
    }
    if (!isset($build['navbar_start']['#attributes'])) {
      $build['navbar_start']['#attributes'] = new Attribute();
    }
    $build['navbar_start']['#attributes']->addClass('navbar-start');

    $build['navbar_end'] = ['#access' => FALSE];
    if ($this->configuration['end_menu']) {
      $build['navbar_end'] = $this->buildMenu(
        $this->configuration['end_menu'],
        $this->configuration['end_menu_level'],
        $this->configuration['end_menu_depth']
      );
      $build['navbar_end']['#access'] = TRUE;
      if (isset($build['navbar_start']['#theme'])) {
        $build['navbar_end']['#theme'] = $this->addNavbarSuggestion($build['navbar_end']['#theme']);
      }
      if (!isset($build['navbar_end']['#attributes'])) {
        $build['navbar_end']['#attributes'] = new Attribute();
      }
      $build['navbar_end']['#attributes']->addClass('navbar-end');
    }

    $build['site_logo'] = [
      '#theme' => 'image',
      '#uri' => theme_get_setting('logo.url'),
      '#alt' => $this->t('Home'),
      '#access' => $this->configuration['use_site_logo'] && theme_get_setting('logo.url'),
    ];

    $build['site_name'] = [
      '#markup' => $site_config->get('name'),
      '#access' => $this->configuration['use_site_name'] && $site_config->get('name'),
    ];

    $build['site_slogan'] = [
      '#markup' => $site_config->get('slogan'),
      '#access' => $this->configuration['use_site_slogan'] && $site_config->get('slogan'),
    ];

    return $build;
  }

  /**
   * Build a menu render array.
   *
   * @see \Drupal\system\Plugin\Block\SystemMenuBlock::build()
   */
  protected function buildMenu($menu_name, $level, $depth) {
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    return $this->menuTree->build($tree);
  }

  /**
   * Add a suggestion to be able to overwrite menu links markup.
   */
  protected function addNavbarSuggestion($themeHook) {
    return str_replace('menu__', 'menu__bulma_navbar__', $themeHook);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $cache_tags = parent::getCacheTags();
    if ($this->configuration['end_menu']) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['end_menu'];
    }
    return Cache::mergeTags(
      $cache_tags,
      $this->configFactory->get('system.site')->getCacheTags()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $menu_name = $this->configuration['end_menu'];
    if ($menu_name) {
      return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . $menu_name]);
    }
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $menuEntity = $this->menuStorage->load($this->configuration['end_menu']);
    if ($menuEntity) {
      $dependencies['config'][] = $menuEntity->getConfigDependencyName();
    }
    return $dependencies;
  }

}
