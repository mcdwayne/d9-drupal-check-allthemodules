<?php

namespace Drupal\sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Url;
use Drupal\sitemap\SitemapManager;

/**
 * Provides a configuration form for sitemap.
 */
class SitemapSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The SitemapMap plugin manager.
   *
   * @var \Drupal\sitemap\SitemapManager
   */
  protected $sitemapManager;

  /**
   * An array of Sitemap plugins.
   *
   * @var \Drupal\sitemap\SitemapInterface[]
   */
  protected $plugins = [];

  /**
   * Constructs a SitemapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\sitemap\SitemapManager $sitemap_manager
   *   The Sitemap plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler, SitemapManager $sitemap_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->sitemapManager = $sitemap_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $module_handler = $container->get('module_handler');
    $form = new static(
      $container->get('config.factory'),
      $module_handler,
      $container->get('plugin.manager.sitemap')
    );
    if ($module_handler->moduleExists('book')) {
      $form->setBookManager($container->get('book.manager'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitemap_settings';
  }

  /**
   * Set book manager service.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   Book manager service to set.
   */
  public function setBookManager(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('sitemap.settings');

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('page_title'),
      '#description' => $this->t('Page title that will be used on the @sitemap_page.', ['@sitemap_page' => $this->l($this->t('sitemap page'), Url::fromRoute('sitemap.page'))]),
    ];

    $sitemap_message = $config->get('message');
    $form['message'] = [
      '#type' => 'text_format',
      '#format' => isset($sitemap_message['format']) ? $sitemap_message['format'] : NULL,
      '#title' => $this->t('Sitemap message'),
      '#default_value' => $sitemap_message['value'],
      '#description' => $this->t('Define a message to be displayed above the sitemap.'),
    ];

    // Retrieve stored configuration for the plugins.
    $plugins = $config->get('plugins');
    $plugin_config = [];

    // Create plugin instances for all available Sitemap plugins, including both
    // enabled/configured ones as well as new and not yet configured ones.
    $definitions = $this->sitemapManager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      if ($this->sitemapManager->hasDefinition($id)) {
        if (!empty($plugins[$id])) {
          $plugin_config = $plugins[$id];
        }
        $this->plugins[$id] = $this->sitemapManager->createInstance($id, $plugin_config);
      }
    }

    // Plugin status.
    $form['plugins']['enabled'] = [
      '#type' => 'item',
      '#title' => $this->t('Available plugins'),
      '#prefix' => '<div id="plugins-enabled-wrapper">',
      '#suffix' => '</div>',
      // This item is used as a pure wrapping container with heading. Ignore its
      // value, since 'plugins' should only contain plugin definitions.
      // See https://www.drupal.org/node/1829202.
      '#input' => FALSE,
    ];
    // SitemapMap order (tabledrag).
    $form['plugins']['order'] = [
      '#type' => 'table',
      // For sitemap.admin.js.
      '#attributes' => ['id' => 'sitemap-order'],
      '#title' => $this->t('Plugin order'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sitemap-plugin-order-weight',
        ],
      ],
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];
    // Map settings.
    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Plugin settings'),
    ];

    // Provide a default weight value.
    $i = -50;

    foreach ($this->plugins as $id => $plugin) {
      /* @var $plugin \Drupal\sitemap\SitemapBase */

      if (!empty($plugin->weight)) {
        $weight = $plugin->weight;
        $i = $weight;
      }
      else {
        $weight = $i;
      }

      $form['plugins']['enabled'][$id] = [
        '#type' => 'checkbox',
        '#title' => $plugin->getLabel(),
        '#default_value' => $plugin->enabled,
        '#parents' => ['plugins', $id, 'enabled'],
        '#description' => $plugin->getDescription(),
        '#weight' => $weight,
      ];

      $form['plugins']['order'][$id]['#attributes']['class'][] = 'draggable';
      $form['plugins']['order'][$id]['#weight'] = $weight;
      $form['plugins']['order'][$id]['filter'] = [
        '#markup' => $plugin->getLabel(),
      ];
      $form['plugins']['order'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $plugin->getLabel()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $weight,
        '#parents' => ['plugins', $id, 'weight'],
        '#attributes' => ['class' => ['plugin-order-weight']],
      ];

      // Retrieve the settings form of the SitemapMap plugins.
      $settings_form = [
        '#parents' => ['plugins', $id, 'settings'],
        '#tree' => TRUE,
      ];
      $settings_form = $plugin->settingsForm($settings_form, $form_state);
      if (!empty($settings_form)) {
        $form['plugins']['settings'][$id] = [
          '#type' => 'details',
          '#title' => $plugin->getLabel(),
          '#open' => TRUE,
          '#weight' => $weight,
          '#parents' => ['plugins', $id, 'settings'],
          '#group' => 'plugin_settings',
        ];
        $form['plugins']['settings'][$id] += $settings_form;

        if (isset($plugins[$id]['settings'])) {
          foreach ($plugins[$id]['settings'] as $key => $value) {
            $form['plugins']['settings'][$id][$key]['#default_value'] = $value;
          }
        }
      }

      // Increment the default weight value.
      $i++;
    }
    $form['#attached']['library'][] = 'sitemap/sitemap.admin';

    // Sitemap RSS settings.
    $form['rss'] = [
      '#type' => 'details',
      '#title' => $this->t('RSS settings'),
    ];
    $form['rss']['rss_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display RSS links:'),
      '#default_value' => $config->get('rss_display'),
      '#options' => [
        'left' => $this->t('Left of the text'),
        'right' => $this->t('Right of the text'),
      ],
      '#description' => $this->t('When enabled, this option will show links to the RSS feeds for the front page and taxonomy terms, if enabled.'),
    ];
    /*
    $form['sitemap_options']['sitemap_rss_options']['rss_taxonomy'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS depth for vocabularies'),
      '#default_value' => $config->get('rss_taxonomy'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many RSS feed links should be displayed with taxonomy terms. Enter "-1" to include with all terms, "0" not to include with any terms, or "1" to show only for top-level taxonomy terms.'),
    );*/

    // Sitemap CSS settings.
    $form['css'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS settings'),
    ];
    $form['css']['include_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include sitemap CSS file'),
      '#default_value' => $config->get('include_css'),
      '#description' => $this->t("Select this box if you wish to load the CSS file included with the module. To learn how to override or specify the CSS at the theme level, visit the @documentation_page.", ['@documentation_page' => $this->l($this->t("documentation page"), Url::fromUri('https://www.drupal.org/node/2615568'))]),
    ];

/*
    if ($this->moduleHandler->moduleExists('book')) {
      $form['sitemap_book_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Book settings'),
      ];
      $form['sitemap_book_options']['books_expanded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show books expanded'),
        '#default_value' => $config->get('books_expanded'),
        '#description' => $this->t('When enabled, this option will show all children pages for each book.'),
      ];
    }

    if ($this->moduleHandler->moduleExists('forum')) {
      $form['sitemap_forum_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Forum settings'),
      ];
      $form['sitemap_forum_options']['forum_threshold'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Forum count threshold'),
        '#default_value' => $config->get('forum_threshold'),
        '#size' => 3,
        '#description' => $this->t('Only show forums whose node counts are greater than this threshold. Set to -1 to disable.'),
      );
    }

    $form['sitemap_menu_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu settings'),
    ];
    $form['sitemap_menu_options']['show_menus_hidden'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show disabled menu items'),
      '#default_value' => $config->get('show_menus_hidden'),
      '#description' => $this->t('When enabled, hidden menu links will also be shown.'),
    );

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $form['sitemap_taxonomy_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Taxonomy settings'),
      ];
      $form['sitemap_taxonomy_options']['show_description'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show vocabulary description'),
        '#default_value' => $config->get('show_description'),
        '#description' => $this->t('When enabled, this option will show the vocabulary description.'),
      ];
      $form['sitemap_taxonomy_options']['show_count'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show node counts by taxonomy terms'),
        '#default_value' => $config->get('show_count'),
        '#description' => $this->t('When enabled, this option will show the number of nodes in each taxonomy term.'),
      ];
      $form['sitemap_taxonomy_options']['vocabulary_depth'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Vocabulary depth'),
        '#default_value' => $config->get('vocabulary_depth'),
        '#size' => 3,
        '#maxlength' => 10,
        '#description' => $this->t('Specify how many levels taxonomy terms should be included. Enter "-1" to include all terms, "0" not to include terms at all, or "1" to only include top-level terms.'),
      ];
      $form['sitemap_taxonomy_options']['term_threshold'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Term count threshold'),
        '#default_value' => $config->get('term_threshold'),
        '#size' => 3,
        '#description' => $this->t('Only show taxonomy terms whose node counts are greater than this threshold. Set to -1 to disable.'),
      ];
    }
*/
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('sitemap.settings');

    // Save config.
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'plugins') {
        foreach ($value as $instance_id => $plugin_config) {
          // Update the plugin configurations
          $this->plugins[$instance_id]->setConfiguration($plugin_config);
        }
        // Save in sitemap.settings
        $config->set($key, $value);
      } else {
        $config->set($key, $value);
      }
    }
    $config->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sitemap.settings'];
  }

}
