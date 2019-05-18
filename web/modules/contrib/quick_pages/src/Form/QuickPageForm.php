<?php

/**
 * @file
 * Contains Drupal\quick_pages\Form\QuickPageForm.
 */

namespace Drupal\quick_pages\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Display\VariantManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\quick_pages\MainContentPluginManager;
use Drupal\views\Plugin\ViewsPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quick page form.
 *
 * @property \Drupal\quick_pages\entity\QuickPage $entity
 */
class QuickPageForm extends EntityForm {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The variant manager.
   *
   * @var \Drupal\Core\Display\VariantManager;
   */
  protected $variantManager;

  /**
   * The variant manager.
   *
   * @var \Drupal\quick_pages\MainContentPluginManager;
   */
  protected $mainContentManager;

  /**
   * The access manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager;
   */
  protected $accessManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a new quick page form instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   *   The variant manager.
   * @param \Drupal\quick_pages\MainContentPluginManager $main_content_manager
   *   The main content manager.
   * @param \Drupal\views\Plugin\ViewsPluginManager $access_manager
   *   Access manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder services.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, VariantManager $variant_manager, MainContentPluginManager $main_content_manager, ViewsPluginManager $access_manager, RouteBuilderInterface $route_builder) {
    $this->themeHandler = $theme_handler;
    $this->variantManager = $variant_manager;
    $this->mainContentManager = $main_content_manager;
    $this->accessManager = $access_manager;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('plugin.manager.display_variant'),
      $container->get('plugin.manager.main_content_provider'),
      $container->get('plugin.manager.views.access'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Administrative label for the page.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\quick_pages\Entity\QuickPage::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#default_value' => $this->entity->get('path'),
      '#autocomplete_route_name' => 'quick_pages.path_autocomplete',
      '#description' => t('This page will be displayed by visiting this path on your site. Make sure the path begins with "/".'),
      '#element_validate' => ['::validatePath'],
      '#required' => TRUE,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $this->entity->get('title'),
      '#description' => t('Page title. Leave the field empty to use default title.'),
    ];

    $themes = $this->themeHandler->rebuildThemeData();
    uasort($themes, 'system_sort_modules_by_info_name');

    $theme_options[''] = t('- Default -');
    foreach ($themes as &$theme) {
      if ($theme->status && empty($theme->info['hidden'])) {
        $theme_options[$theme->getName()] = $theme->info['name'];
      }
    }

    $form['theme'] = [
      '#type' => 'select',
      '#title' => t('Theme'),
      '#options' => $theme_options,
      '#default_value' => $this->entity->get('theme'),
    ];

    $display_variant_wrapper = 'display-variant-settings';
    $form['display_variant'] = [
      '#type' => 'details',
      '#title' => t('Display variant settings'),
      '#open' => TRUE,
      '#id' => $display_variant_wrapper,
     ];

    $variant_definitions = $this->variantManager->getDefinitions();
    $options = ['' => t('- Default -')];
    foreach ($variant_definitions as $id => $definition) {
      $options[$id] = $definition['admin_label'];
    }
    asort($options);

    $form['display_variant']['id'] = [
      '#type' => 'select',
      '#title' => t('Display variant'),
      '#options' => $options,
      '#default_value' => $this->entity->get('display_variant'),
      '#ajax' => [
        'wrapper' => $display_variant_wrapper,
        'callback' => '::ajaxSettings',
        'event' => 'change',
      ],
    ];

    $display_variant = $this->entity->get('display_variant');
    if ($display_variant && $display_variant['id']) {
      $variant_configuration = isset($display_variant['configuration']) ?
        $display_variant['configuration'] : [];
      $variant_instance = $this->variantManager->createInstance($display_variant['id'], $variant_configuration);
      $form['display_variant']['configuration'] = $variant_instance->buildConfigurationForm([], $form_state);
    }

    $main_content_wrapper = 'main-content-settings';
    $form['main_content_provider'] = [
      '#type' => 'details',
      '#title' => t('Main content settings'),
      '#open' => TRUE,
      '#id' => $main_content_wrapper,
    ];

    $main_content_definitions = $this->mainContentManager->getDefinitions();
    $options = ['' => t('- Default -')];
    foreach ($main_content_definitions as $id => $definition) {
      $options[$id] = $definition['title'];
    }
    asort($options);

    $form['main_content_provider']['id'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => $options,
      '#default_value' => $this->entity->get('main_content_provider'),
      '#ajax' => [
        'wrapper' => $main_content_wrapper,
        'callback' => '::ajaxSettings',
        'event' => 'change',
      ],
    ];

    $main_content_provider = $this->entity->get('main_content_provider');
    if ($main_content_provider && $main_content_provider['id']) {
      $plugin_configuration = isset($main_content_provider['configuration']) ?
        $main_content_provider['configuration'] : [];

      $provider_instance = $this->mainContentManager->createInstance($main_content_provider['id'], $plugin_configuration);
      $form['main_content_provider']['configuration'] = $provider_instance->buildConfigurationForm([], $form_state);
    }

    $access_wrapper = 'access-settings';
    $form['access'] = [
      '#type' => 'details',
      '#title' => t('Access settings'),
      '#open' => TRUE,
      '#id' => $access_wrapper,
    ];

    $access_definitions = $this->accessManager->getDefinitions();
    $options = [];
    foreach ($access_definitions as $id => $definition) {
      $options[$id] = $definition['title'];
    }
    asort($options);

    $form['access']['id'] = [
      '#type' => 'select',
      '#title' => t('Access'),
      '#options' => $options,
      '#default_value' => $this->entity->get('access'),
      '#ajax' => [
        'wrapper' => $access_wrapper,
        'callback' => '::ajaxSettings',
        'event' => 'change',
      ],
    ];

    $access = $this->entity->get('access');
    if ($access && $access['id']) {

      // @todo: Find a better way to initialize default plugin settings.
      if ($access['id'] == 'role') {
        $default_configuration = ['role' => []];
      }
      elseif ($access['id'] == 'perm') {
        $default_configuration = ['perm' => 'access content'];
      }
      else {
        $default_configuration = [];
      }

      $plugin_configuration = isset($access['configuration']) ?
        $access['configuration'] : $default_configuration;

      $access_instance = $this->accessManager->createInstance($access['id']);
      // @todo: Find a better way to init the plugin.
      $access_instance->options = $plugin_configuration;

      $access_instance->buildOptionsForm($form['access']['configuration'], $form_state);
    }

    return $form;
  }

  /**
   * Validates the path of the quick page.
   */
  public static function validatePath($element, FormStateInterface $form_state, array $form) {

    $path = $form_state->getValue('path');

    $parsed_url = UrlHelper::parse($path);
    if ($parsed_url['path'][0] != '/') {
      $form_state->setErrorByName('path', t('The path should begin with "/".'));
    }

    if (count($parsed_url['query']) > 0) {
      $form_state->setErrorByName('path', t('No query allowed.'));
    }

    if ($parsed_url['fragment']) {
      $form_state->setErrorByName('path', t('No fragment allowed.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $this->entity->save();
    drupal_set_message($this->t('Saved the %label quick page.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
    $this->routeBuilder->rebuild();
  }

  /**
   * Ajax callback.
   */
  public function ajaxSettings(array &$form, FormStateInterface $form_state) {
    return $form[$form_state->getTriggeringElement()['#parents'][0]];
  }

}
