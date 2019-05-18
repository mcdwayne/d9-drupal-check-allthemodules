<?php

namespace Drupal\editor_md\Plugin\Editor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Editor.md-based text editor for Drupal.
 *
 * @Editor(
 *   id = "editor_md",
 *   label = @Translation("Editor.md"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class EditorMd extends EditorBase implements ContainerFactoryPluginInterface {

  /**
   * A list of predefined toolbar modes.
   *
   * @var array
   */
  protected static $toolbarModes = [
    'full' => [
      'bold', 'italic', 'quote', '|',
      'h2', 'h3', 'h4', '|',
      'list-ul', 'list-ol', 'hr', '|',
      'link', 'reference-link', 'image', '|',
      'code', 'preformatted-text', 'code-block', '|',
      'table', 'datetime', 'emoji', 'html-entities', '|',
      'goto-line', 'watch', 'preview', 'fullscreen', 'clear', 'search'
    ],
    'simple' => [
      'bold', 'italic', 'quote', '|',
      'h2', 'h3', 'h4', '|',
      'list-ul', 'list-ol', 'hr', '|',
      'watch', 'preview', 'fullscreen'
    ],
    'mini' => [
      'bold', 'italic', 'quote', 'link', 'image', 'list-ul', 'list-ol', 'hr'
    ],
  ];

  /**
   * The discovery cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $discoveryCache;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $discovery_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->discoveryCache = $discovery_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'editorTheme' => 'base16-light',
      'height' => '440px',
      'mode' => 'gfm',
      'path' => (string) Url::fromUserInput('/libraries/editor.md/lib/')->toString(),
      'pluginPath' => (string) Url::fromUserInput('/libraries/editor.md/plugins/')->toString(),
      'previewTheme' => 'default',
      'theme' => 'default',
      'toolbar' => TRUE,
      'toolbarAutoFixed' => TRUE,
      'toolbarMode' => 'full',
      'toolbarIcons' => $this->getToolbarMode('full'),
      'watch' => FALSE,
      'width' => '100%',
    ];
  }

  /**
   * Retrieves the library path.
   *
   * @return string
   *   The library path.
   */
  protected function getLibraryPath() {
    return 'libraries/editor.md';
  }

  /**
   * Retrieves the available CodeMirror editor themes.
   *
   * @return array
   *   An array of editor theme names.
   */
  protected function getEditorThemes() {
    $cid = 'editor_md:editor.themes';
    if (($cache = $this->discoveryCache->get($cid)) && isset($cache->data)) {
      return $cache->data;
    }

    $themes = [];
    $files = file_scan_directory($this->getLibraryPath() . '/lib/codemirror/theme', '/\.css$/');
    foreach ($files as $file) {
      $themes[$file->name] = $file->name;
    }

    ksort($themes);

    $this->discoveryCache->set($cid, $themes);

    return $themes;
  }

  /**
   * Retrieves the toolbar icons for a specific mode.
   *
   * @param string $mode
   *   The toolbar mode to retrieve.
   *
   * @return array
   *   An indexed array of toolbar icons.
   */
  public function getToolbarMode($mode) {
    return static::$toolbarModes[$mode];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['tabs'] = ['#type' => 'vertical_tabs'];

    // General.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#group' => 'editor][settings][tabs',
    ];

    $form['general']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'gfm' => $this->t('gfm (GitHub Flavored Markdown)'),
        'markdown' => $this->t('markdown'),
      ],
      '#default_value' => $settings['mode'],
    ];

    $form['general']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $settings['height'],
    ];

    $form['general']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $settings['width'],
    ];

    $form['general']['watch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Watch/Preview'),
      '#default_value' => $settings['watch'],
    ];

    // Themes.
    $form['themes'] = [
      '#type' => 'details',
      '#title' => $this->t('Themes'),
      '#group' => 'editor][settings][tabs',
    ];

    $form['themes']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Editor.md (container, toolbar, etc.)'),
      '#options' => [
        'default' => $this->t('Light (default)'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $settings['theme'],
    ];

    $form['themes']['editorTheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Editor'),
      '#options' => $this->getEditorThemes(),
      '#default_value' => $settings['editorTheme'],
    ];

    $form['themes']['previewTheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Preview'),
      '#options' => [
        'default' => $this->t('Light (default)'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $settings['previewTheme'],
    ];

    // Toolbar.
    // @todo Add a more dynamic drag 'n drop UI like CKEditor does.
    $form['toolbar'] = [
      '#type' => 'details',
      '#title' => $this->t('Toolbar'),
      '#group' => 'editor][settings][tabs',
    ];

    $form['toolbar']['toolbar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $settings['toolbar'],
    ];

    $form['toolbar']['toolbarAutoFixed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Fixed'),
      '#default_value' => $settings['toolbarAutoFixed'],
      '#description' => $this->t('Keeps the toolbar at the top when scrolling.'),
      '#states' => [
        'visible' => [
          '[data-drupal-selector="edit-editor-settings-toolbar-toolbar"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['toolbar']['toolbarMode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'full' => $this->t('Full'),
        'simple' => $this->t('Simple'),
        'mini' => $this->t('Mini'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $settings['toolbarMode'],
      '#states' => [
        'visible' => [
          '[data-drupal-selector="edit-editor-settings-toolbar-toolbar"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['toolbar']['toolbarIcons'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom'),
      '#default_value' => implode(', ', $settings['toolbarIcons']),
      '#states' => [
        'visible' => [
          '[data-drupal-selector="edit-editor-settings-toolbar-toolbar"]' => ['checked' => TRUE],
          '[data-drupal-selector="edit-editor-settings-toolbar-toolbarmode"]' => ['value' => 'custom'],
        ],
      ],
      '#description' => $this->t('A comma separated value (CSV) list of toolbar icon/plugin names. To separate them into groups, use a pipe (|) in between icons.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit(array $form, FormStateInterface $form_state) {
    $settings = [];
    foreach (['general', 'themes', 'toolbar'] as $group) {
      $settings = NestedArray::mergeDeep($settings, $form_state->getValue(['editor', 'settings', $group]));
    }

    // Replace the toolbar icons with a real mode if one was selected.
    if ($settings['toolbarMode'] !== 'custom') {
      $settings['toolbarIcons'] = $this->getToolbarMode($settings['toolbarMode']);
    }

    // Normalize toolbar icons into an array.
    $settings['toolbarIcons'] = $this->normalizeToolbarIcons($settings['toolbarIcons']);

    // Now replace the form's value with the real settings.
    $form_state->setValue(['editor', 'settings'], $settings);
  }

  /**
   * Normalizes the toolbar icons into an array suitable for Editor.md.
   *
   * @param array|string $icons
   *   An array of icons or a CSV separated list of icons.
   *
   * @return array
   *   An array of toolbar icons.
   */
  protected function normalizeToolbarIcons($icons) {
    // Convert CSV based icons into an array.
    if (is_string($icons)) {
      // Filter for XSS (just in case) since a string indicates user input.
      $icons = explode(',', Xss::filterAdmin($icons));
    }
    return array_map('trim', $icons);
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $settings = $editor->getSettings();

    $settings['lang'] = [
      'description' => $this->t('Open source online Markdown editor.'),
      'tocTitle' => $this->t('Table of Contents'),
      'toolbar' => [
        'undo' => $this->t('Undo'),
        'redo' => $this->t('Redo'),
        'bold' => $this->t('Bold'),
        'del' => $this->t('Strikethrough'),
        'italic' => $this->t('Italic'),
        'quote' => $this->t('Block quote'),
        'ucwords' => $this->t('Words first letter convert to uppercase'),
        'uppercase' => $this->t('Selection text convert to uppercase'),
        'lowercase' => $this->t('Selection text convert to lowercase'),
        'h1' => $this->t('Heading 1'),
        'h2' => $this->t('Heading 2'),
        'h3' => $this->t('Heading 3'),
        'h4' => $this->t('Heading 4'),
        'h5' => $this->t('Heading 5'),
        'h6' => $this->t('Heading 6'),
        'list-ul' => $this->t('Unordered list'),
        'list-ol' => $this->t('Ordered list'),
        'hr' => $this->t('Horizontal rule'),
        'link' => $this->t('Link'),
        'reference-link' => $this->t('Reference link'),
        'image' => $this->t('Image'),
        'code' => $this->t('Code inline'),
        'preformatted-text' => $this->t(
          'Preformatted text / Code block (Tab indent)'
        ),
        'code-block' => $this->t('Code block (Multi-languages)'),
        'table' => $this->t('Tables'),
        'datetime' => $this->t('Datetime'),
        'emoji' => $this->t('Emoji'),
        'html-entities' => $this->t('HTML Entities'),
        'pagebreak' => $this->t('Page break'),
        'watch' => $this->t('Unwatch'),
        'unwatch' => $this->t('Watch'),
        'preview' => $this->t('HTML Preview'),
        'fullscreen' => $this->t('Fullscreen'),
        'clear' => $this->t('Clear'),
        'search' => $this->t('Search'),
        'help' => $this->t('Help'),
        'info' => $this->t(
          'About %editor_label',
          ['%editor_label' => $editor->label()]
        ),
        'superscript' => $this->t('Superscript'),
        'subscript' => $this->t('Subscript'),
      ],
      'buttons' => [
        'enter' => $this->t('Enter'),
        'cancel' => $this->t('Cancel'),
        'close' => $this->t('Close'),
      ],
      'dialog' => [
        'link' => [
          'title' => $this->t('Link'),
          'url' => $this->t('Address'),
          'urlTitle' => $this->t('Title'),
          'urlEmpty' => $this->t('Error: Please fill in the link address.'),
        ],
        'referenceLink' => [
          'title' => $this->t('Reference link'),
          'name' => $this->t('Name'),
          'url' => $this->t('Address'),
          'urlId' => $this->t('ID'),
          'urlTitle' => $this->t('Title'),
          'nameEmpty' => $this->t("Error: Reference name can't be empty."),
          'idEmpty' => $this->t('Error: Please fill in reference link id.'),
          'urlEmpty' => $this->t(
            'Error: Please fill in reference link url address.'
          ),
        ],
        'image' => [
          'title' => $this->t('Image'),
          'url' => $this->t('Address'),
          'link' => $this->t('Link'),
          'alt' => $this->t('Title'),
          'uploadButton' => $this->t('Upload'),
          'imageURLEmpty' => $this->t(
            "Error: picture url address can't be empty."
          ),
          'uploadFileEmpty' => $this->t(
            'Error: upload pictures cannot be empty!'
          ),
          'formatNotAllowed' => $this->t(
            'Error: only allows to upload pictures file, upload allowed image file format:'
          ),
        ],
        'preformattedText' => [
          'title' => $this->t('Preformatted text / Codes'),
          'emptyAlert' => $this->t(
            'Error: Please fill in the Preformatted text or content of the codes.'
          ),
        ],
        'codeBlock' => [
          'title' => $this->t('Code block'),
          'selectLabel' => $this->t('Languages: '),
          'selectDefaultText' => $this->t('Select a code language...'),
          'otherLanguage' => $this->t('Other languages'),
          'unselectedLanguageAlert' => $this->t(
            'Error: Please select the code language.'
          ),
          'codeEmptyAlert' => $this->t(
            'Error: Please fill in the code content.'
          ),
        ],
        'htmlEntities' => [
          'title' => $this->t('HTML Entities'),
        ],
        'help' => [
          'title' => $this->t('Help'),
        ],
        'table' => [
          'title' => $this->t('Tables'),
          'cellsLabel' => $this->t('Cells'),
          'alignLabel' => $this->t('Align'),
          'rows' => $this->t('Rows'),
          'cols' => $this->t('Cols'),
          'aligns' => [
            $this->t('Default'),
            $this->t('Left align'),
            $this->t('Center align'),
            $this->t('Right align'),
          ],
        ],
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $libraries = [
      'editor_md/editor_md',
      'editor_md/drupal.editor_md',
    ];
    return $libraries;
  }

}
