<?php

namespace Drupal\ckeditor5_sections\Plugin\Editor;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

// TODO: Apply linkit conditionally.

// TODO: Make the default root work.

// TODO: Remove all unused services from this file.

/**
 * Defines a CKEditor5-based text editor for Drupal.
 *
 * @Editor(
 *   id = "ckeditor5_sections",
 *   label = @Translation("CKEditor5 Sections"),
 *   supports_content_filtering = FALSE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea",
 *     "textfield",
 *   }
 * )
 */
class CKEditor5Sections extends EditorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance counter to track instances on a specific page request.
   */
  static $instances = 0;

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CKEditor plugin manager.
   *
   * @var \Drupal\ckeditor\CKEditorPluginManager
   */
  protected $ckeditorPluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a \Drupal\ckeditor\Plugin\Editor\CKEditor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager
   *   The CKEditor plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    CKEditorPluginManager $ckeditor_plugin_manager,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager,
    RendererInterface $renderer,
    CacheBackendInterface $cacheBackend,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ckeditorPluginManager = $ckeditor_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->cache = $cacheBackend;
    try {
      $this->linkitProfileStorage = $entityTypeManager->getStorage('linkit_profile');
    } catch (PluginNotFoundException $exc) {
      // Ignore this case. LinkIt is probably not be installed.
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ckeditor.plugin'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('cache.config'),
      $container->get('entity_type.manager'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'templateDirectory' => drupal_get_path('module', 'ckeditor5_sections') . '/sections',
      'rootElement' => '',
      'enabledSections' => [],
      'editorBuild' => 'ckeditor5_sections/editor_build',
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => TRUE,
          'linkit_profile' => 'default',
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $templateDirectory = $form_state->getValue(['editor','settings', 'templateDirectory'], $settings['templateDirectory']);
    $sections = $this->collectSections($templateDirectory);

    $form['templateDirectory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template directory'),
      '#description' => $this->t('The directory that will be scanned for editor templates.'),
      '#default_value' => $settings['templateDirectory'],
      '#ajax' => [
        'disable-refocus' => 'true',
        'callback' => [self::class, 'templateListAjax'],
        'wrapper' => 'ckeditor5-sections-template-list',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Updating templates entry...'),
        ],
      ],
    ];

    $form['rootElement'] = [
      '#prefix' => '<div id="ckeditor5-sections-template-list">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#title' => $this->t('Root Element'),
      '#options' => ['__default' => t('Default Root Element')] + array_map(function ($section) {
          return $section['label'];
        }, $sections),
      '#default_value' => $settings['rootElement'],
    ];

    $form['enabledSections'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enabled sections'),
      '#options' => array_map(function ($section) {
        return $section['label'];
      }, $sections),
      '#default_value' => $settings['enabledSections'],
      '#min' => 1,
      '#states' => [
        'required' => [
          'select[name*="rootElement"]' => ['value' => '__default'],
        ],
        'visible' => [
          'select[name*="rootElement"]' => ['value' => '__default'],
        ],
      ],
    ];

    $builds = [];
    foreach (system_get_info('module') as $module) {
      if (array_key_exists('ckeditor5_sections_builds', $module)) {
        foreach ($module['ckeditor5_sections_builds'] as $library) {
          $builds[$library] = $library;
        }
      }
    }

    $form['editorBuild'] = [
      '#type' => 'select',
      '#title' => $this->t('Editor build'),
      '#description' => $this->t('Choose one of the available builds.'),
      '#default_value' => $settings['editorBuild'],
      '#options' => $builds,
    ];

    if ($this->linkitProfileStorage) {
      $all_profiles = $this->linkitProfileStorage->loadMultiple();

      $options = [];
      foreach ($all_profiles as $profile) {
        $options[$profile->id()] = $profile->label();
      }

      $form['plugins']['drupallink'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('LinkIt integration'),
      ];

      $linkit = &$form['plugins']['drupallink'];

      $linkit['linkit_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Linkit enabled'),
        '#default_value' => isset($settings['plugins']['drupallink']['linkit_enabled']) ? $settings['plugins']['drupallink']['linkit_enabled'] : '',
        '#description' => $this->t('Enable Linkit for this text format.'),
      ];

      $linkit['linkit_profile'] = [
        '#type' => 'select',
        '#title' => $this->t('Linkit profile'),
        '#options' => $options,
        '#default_value' => isset($settings['plugins']['drupallink']['linkit_profile']) ? $settings['plugins']['drupallink']['linkit_profile'] : '',
        '#empty_option' => $this->t('- Select -'),
        '#description' => $this->t('Select the Linkit profile you wish to use with this text format.'),
        '#states' => [
          'invisible' => [
            'input[data-drupal-selector="edit-editor-settings-plugins-drupallink-linkit-enabled"]' => ['checked' => FALSE],
          ],
        ],
        '#element_validate' => [
          [$this, 'validateLinkitProfileSelection'],
        ],
      ];
    }

    return $form;
  }

  public static function templateListAjax($form, FormStateInterface $form_state) {
    $path = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -1);
    $path[] = 'rootElement';
    return NestedArray::getValue($form, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['ckeditor5_sections/editor'];
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $settings = $editor->getSettings();
    $sections = $this->collectSections($settings['templateDirectory']);
    $enabledSections = array_filter(array_values($settings['enabledSections']));
    $rootElement = $settings['rootElement'];

    if ($rootElement == '__default') {
      $sections['_root'] = [
        'label' => $this->t('Document root'),
        'template' => '<div class="root" ck-type="container" ck-contains="' . implode(' ', $enabledSections) . '"></div>',
      ];
      $settings['masterTemplate'] = '_root';
    }
    else {
      $settings['masterTemplate'] = $rootElement;
    }

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $moduleHandler->alter('ckeditor5_sections_attributes', $templateAttributes);

    $settings['templates'] = $sections;
    $settings['templateAttributes'] = $templateAttributes;
    $settings['templateSession'] = implode(':', [session_id(), time(), static::$instances++ ]);
    $settings['enabled_drupal_modules'] = array_keys($moduleHandler->getModuleList());

    $moduleHandler->alter('ckeditor5_sections_editor_settings', $settings);

    return $settings;
  }

  /**
   * Returns a list of all available sections.
   *
   * @param string $directory
   *   The directory path to scan for templates.
   *
   * @return array
   */
  protected function collectSections($directory) {
    $files = file_scan_directory($directory, '/.*.yml/');
    $sections = [];
    foreach ($files as $file => $fileInfo) {
      $info = \Symfony\Component\Yaml\Yaml::parseFile($file);
      $sections[$fileInfo->name] = [
        'label' => array_key_exists('label', $info) ? $info['label'] : $fileInfo->name,
        'icon' => array_key_exists('icon', $info) ? $info['icon'] : 'text',
        'template' => file_get_contents(dirname($fileInfo->uri) . '/' . $fileInfo->name . '.html'),
      ];
    }
    return $sections;
  }

  /**
   * Linkit profile select validation.
   *
   * #element_validate callback for the "linkit_profile" element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public function validateLinkitProfileSelection(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue([
      'editor',
      'settings',
      'plugins',
      'drupallink',
    ]);
    $enabled = isset($values['linkit_enabled']) && $values['linkit_enabled'] === 1;
    if ($enabled && empty(trim($values['linkit_profile']))) {
      $form_state->setError($element, $this->t('Please select the Linkit profile you wish to use.'));
    }
  }

}
