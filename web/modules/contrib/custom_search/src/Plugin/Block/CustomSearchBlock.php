<?php

namespace Drupal\custom_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Custom Search form' block.
 *
 * @Block(
 *   id = "custom_search",
 *   category = @Translation("Forms"),
 *   admin_label = @Translation("Custom Search form")
 * )
 */
class CustomSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorInterface
   */
  protected $errorHandler;

  /**
   * Constructs a new CustomSearchBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module handler object.
   */
  public function __construct(
  array $configuration,
  $plugin_id,
  $plugin_definition,
  ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('search content')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'search_box' => [
        'label_visibility' => FALSE,
        'label' => $this->t('Search this site'),
        'placeholder' => '',
        'title' => $this->t('Enter the terms you wish to search for.'),
        'size' => 15,
        'max_length' => 128,
        'weight' => -9,
        'region' => 'block',
      ],
      'submit' => [
        'text' => $this->t('Search'),
        'image_path' => '',
        'weight' => 9,
        'region' => 'block',
      ],
      'content' => [
        'types' => [],
        'other' => [],
        'selector' => [
          'type' => 'select',
          'label_visibility' => TRUE,
          'label' => $this->t('Search for'),
        ],
        'any' => [
          'text' => $this->t('- Any -'),
          'restricts' => FALSE,
          'force' => FALSE,
        ],
        'excluded' => [],
        'weight' => -8,
        'region' => 'block',
      ],
      'criteria' => [
        'or' => [
          'display' => FALSE,
          'label' => $this->t('Containing any of the words'),
          'weight' => 4,
          'region' => 'block',
        ],
        'phrase' => [
          'display' => FALSE,
          'label' => $this->t('Containing the phrase'),
          'weight' => 5,
          'region' => 'block',
        ],
        'negative' => [
          'display' => FALSE,
          'label' => $this->t('Containing none of the words'),
          'weight' => 6,
          'region' => 'block',
        ],
      ],
      'languages' => [
        'languages' => [],
        'selector' => [
          'type' => 'select',
          'label_visibility' => TRUE,
          'label' => $this->t('Languages'),
        ],
        'any' => [
          'text' => $this->t('- Any -'),
          'restricts' => FALSE,
          'force' => FALSE,
        ],
        'weight' => 7,
        'region' => 'block',
      ],
      'paths' => [
        'list' => '',
        'selector' => [
          'type' => 'select',
          'label_visibility' => TRUE,
          'label' => $this->t('Customize your search'),
        ],
        'separator' => '+',
        'weight' => 8,
        'region' => 'block',
      ],
    ];

    $search_pages = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() == 'node_search' && $page->isDefaultSearch()) {
        $defaults['content']['page'] = $page->id();
        break;
      }
    }

    $vocabularies = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    $vocWeight = -7;
    foreach ($vocabularies as $voc) {
      $vocId = $voc->id();
      $defaults['taxonomy'][$vocId]['type'] = 'disabled';
      $defaults['taxonomy'][$vocId]['depth'] = 0;
      $defaults['taxonomy'][$vocId]['label_visibility'] = TRUE;
      $defaults['taxonomy'][$vocId]['label'] = $voc->label();
      $defaults['taxonomy'][$vocId]['all_text'] = t('- Any -');
      $defaults['taxonomy'][$vocId]['region'] = 'block';
      $defaults['taxonomy'][$vocId]['weight'] = $vocWeight;
      $vocWeight++;
    }

    return $defaults;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Labels & default text.
    $form['search_box'] = [
      '#type' => 'details',
      '#title' => $this->t('Search box'),
      '#open' => TRUE,
    ];
    $form['search_box']['label_visibility'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['search_box']['label_visibility'],
    ];
    $form['search_box']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['search_box']['label'],
      '#description' => $this->t('Enter the label text for the search box. The default value is "Search this site".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[search_box][label_visibility]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['search_box']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text'),
      '#default_value' => $this->configuration['search_box']['placeholder'],
      '#description' => $this->t('This will change the default text inside the search form. This is the <a href="http://www.w3schools.com/tags/att_input_placeholder.asp" target="_blank">placeholder</a> attribute for the TextField. Leave blank for no text. This field is blank by default.'),
    ];
    $form['search_box']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hint text'),
      '#default_value' => $this->configuration['search_box']['title'],
      '#description' => $this->t('Enter the text that will be displayed when hovering the input field (HTML <em>title</em> attritube).'),
    ];
    $form['search_box']['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size'),
      '#size' => 3,
      '#default_value' => $this->configuration['search_box']['size'],
      '#description' => $this->t('The default value is "@default".', ['@default' => 15]),
    ];
    $form['search_box']['max_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum length'),
      '#size' => 3,
      '#default_value' => $this->configuration['search_box']['max_length'],
      '#description' => $this->t('The default value is "@default".', ['@default' => 128]),
      '#required' => TRUE,
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'details',
      '#title' => $this->t('Submit button'),
      '#open' => TRUE,
    ];
    $form['submit']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#default_value' => $this->configuration['submit']['text'],
      '#description' => $this->t('Enter the text for the submit button. Leave blank to hide it. The default value is "Search".'),
    ];
    if ($this->moduleHandler->moduleExists('file')) {
      $form['submit']['image_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image path'),
        '#description' => $this->t('The path to the file you would like to use as submit button instead of the default text button.'),
        '#default_value' => $this->configuration['submit']['image_path'],
      ];
      $friendly_path = NULL;
      $default_image = 'search.png';
      if (\Drupal::service('file_system')->uriScheme($this->configuration['submit']['image_path']) == 'public') {
        $friendly_path = file_uri_target($this->configuration['submit']['image_path']);
      }
      if ($this->configuration['submit']['image_path'] && isset($friendly_path)) {
        $local_file = strtr($this->configuration['submit']['image_path'], ['public:/' => PublicStream::basePath()]);
      }
      else {
        $local_file = \Drupal::theme()->getActiveTheme()->getPath() . '/' . $default_image;
      }

      $form['submit']['image_path']['#description'] = t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', [
        '@implicit-public-file' => isset($friendly_path) ? $friendly_path : $default_image,
        '@explicit-file' => \Drupal::service('file_system')->uriScheme($this->configuration['submit']['image_path']) !== FALSE ? $this->configuration['submit']['image_path'] : 'public://' . $default_image,
        '@local-file' => $local_file,
      ]);
      $form['submit']['image'] = [
        '#type' => 'file',
        '#title' => $this->t('Image'),
        '#description' => $this->t("If you don't have direct file access to the server, use this field to upload your image."),
      ];
    }

    // Content.
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#description' => $this->t("Select the search types to present as search options in the search block. If none is selected, no selector will be displayed. <strong>Note</strong>: if there's only one type checked, the selector won't be displayed BUT only this type will be searched."),
      '#open' => (count(array_filter($this->configuration['content']['types'])) + count(array_filter($this->configuration['content']['excluded']))),
    ];
    $search_pages = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    $pages_options = [];
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() == 'node_search') {
        $pages_options[$page->id()] = $page->label();
      }
    }
    if (count($pages_options)) {
      $form['content']['page'] = [
        '#type' => 'select',
        '#title' => $this->t('Search page'),
        '#description' => $this->t('Select which page to use when searching content with this block. Pages are defined <a href=":link">here</a>.', [
          ':link' => Url::fromRoute('entity.search_page.collection', [], [
            'fragment' => 'edit-search-pages',
          ])->toString(),
        ]),
        '#default_value' => $this->configuration['content']['page'],
        '#options' => $pages_options,
      ];
    }
    $form['content']['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#default_value' => $this->configuration['content']['types'],
      '#options' => node_type_get_names(),
    ];
    $other_pages_options = [];
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() != 'node_search') {
        $other_pages_options[$page->id()] = $page->label();
      }
    }
    if (count($other_pages_options)) {
      $form['content']['other'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Other search pages'),
        '#default_value' => $this->configuration['content']['other'],
        '#options' => $other_pages_options,
      ];
    }
    $form['content']['selector']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector type'),
      '#options' => [
        'select' => $this->t('Drop-down list'),
        'selectmultiple' => $this->t('Drop-down list with multiple choices'),
        'radios' => $this->t('Radio buttons'),
        'checkboxes' => $this->t('Checkboxes'),
      ],
      '#description' => $this->t('Choose which selector type to use. Note: content types and other searches cannot be combined in a single search.'),
      '#default_value' => $this->configuration['content']['selector']['type'],
    ];
    $form['content']['selector']['label_visibility'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['content']['selector']['label_visibility'],
    ];
    $form['content']['selector']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label text'),
      '#default_value' => $this->configuration['content']['selector']['label'],
      '#description' => $this->t('Enter the label text for the selector. The default value is "Search for".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[content][selector][label_visibility]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['content']['any'] = [
      '#type' => 'details',
      '#title' => $this->t('- Any -'),
    ];
    $form['content']['any']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('- Any content type - text'),
      '#default_value' => $this->configuration['content']['any']['text'],
      '#required' => TRUE,
      '#description' => $this->t('Enter the text for "any content type" choice. The default value is "- Any -".'),
    ];
    $form['content']['any']['restricts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Choosing - Any - restricts the search to the selected content types.'),
      '#default_value' => $this->configuration['content']['any']['restricts'],
      '#description' => $this->t('If not checked, choosing - Any - will search in all content types.'),
    ];
    $form['content']['any']['force'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force - Any - to be displayed.'),
      '#default_value' => $this->configuration['content']['any']['force'],
      '#description' => $this->t('When only one content type is selected, the default behaviour is to hide the selector. If you need the - Any - option to be displayed, check this.'),
    ];
    $form['content']['excluded'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content exclusion'),
      '#description' => $this->t("Select the content types you don't want to be displayed as results."),
      '#default_value' => $this->configuration['content']['excluded'],
      '#options' => node_type_get_names(),
    ];

    // Taxonomy.
    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    if (count($vocabularies)) {
      $open = FALSE;
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        if ($this->configuration['taxonomy'][$vocId]['type'] != 'disabled') {
          $open = TRUE;
          break;
        }
      }
      $form['taxonomy'] = [
        '#type' => 'details',
        '#title' => $this->t('Taxonomy'),
        '#description' => $this->t('Select the vocabularies to present as search options in the search block. If none is selected, no selector will be displayed.'),
        '#open' => $open,
      ];
      // Get vocabularies forms.
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $form['taxonomy'][$vocId] = [
          '#type' => 'details',
          '#title' => $voc->label(),
          '#open' => $this->configuration['taxonomy'][$vocId]['type'] != 'disabled',
        ];
        $form['taxonomy'][$vocId]['type'] = [
          '#type' => 'select',
          '#title' => $this->t('Selector type'),
          '#options' => [
            'disabled' => $this->t('Disabled'),
            'select' => $this->t('Drop-down list'),
            'selectmultiple' => $this->t('Drop-down list with multiple choices'),
            'radios' => $this->t('Radio buttons'),
            'checkboxes' => $this->t('Checkboxes'),
          ],
          '#description' => $this->t('Choose which selector type to use.'),
          '#default_value' => $this->configuration['taxonomy'][$vocId]['type'],
        ];
        $form['taxonomy'][$vocId]['depth'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Depth'),
          '#size' => 2,
          '#default_value' => $this->configuration['taxonomy'][$vocId]['depth'],
          '#description' => $this->t('Define the maximum depth of terms being displayed. The default value is "0" which disables the limit.'),
        ];
        $form['taxonomy'][$vocId]['label_visibility'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Display label'),
          '#default_value' => $this->configuration['taxonomy'][$vocId]['label_visibility'],
        ];
        $form['taxonomy'][$vocId]['label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Label text'),
          '#default_value' => $this->configuration['taxonomy'][$vocId]['label'],
          '#description' => $this->t('Enter the label text for the selector. The default value is "@default".', ['@default' => $voc->label()]),
          '#states' => [
            'visible' => [
              ':input[name="settings[taxonomy][' . $vocId . '][label_visibility]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['taxonomy'][$vocId]['all_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('-Any- text'),
          '#default_value' => $this->configuration['taxonomy'][$vocId]['all_text'],
          '#required' => TRUE,
          '#description' => $this->t('Enter the text for "any term" choice. The default value is "- Any -".'),
        ];
      }
    }

    // Criteria.
    $form['criteria'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced search criteria'),
      '#open' => $this->configuration['criteria']['or']['display'] || $this->configuration['criteria']['phrase']['display'] || $this->configuration['criteria']['negative']['display'],
    ];
    $form['criteria']['or'] = [
      '#type' => 'details',
      '#title' => $this->t('Or'),
      '#open' => $this->configuration['criteria']['or']['display'],
    ];
    $form['criteria']['or']['display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['criteria']['or']['display'],
    ];
    $form['criteria']['or']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['criteria']['or']['label'],
      '#description' => $this->t('Enter the label text for this field. The default value is "Containing any of the words".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[criteria][or][display]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['criteria']['phrase'] = [
      '#type' => 'details',
      '#title' => $this->t('Phrase'),
      '#open' => $this->configuration['criteria']['phrase']['display'],
    ];
    $form['criteria']['phrase']['display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['criteria']['phrase']['display'],
    ];
    $form['criteria']['phrase']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['criteria']['phrase']['label'],
      '#description' => $this->t('Enter the label text for this field. The default value is "Containing the phrase".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[criteria][phrase][display]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['criteria']['negative'] = [
      '#type' => 'details',
      '#title' => $this->t('Negative'),
      '#open' => $this->configuration['criteria']['negative']['display'],
    ];
    $form['criteria']['negative']['display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['criteria']['negative']['display'],
    ];
    $form['criteria']['negative']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['criteria']['negative']['label'],
      '#description' => $this->t('Enter the label text for this field. The default value is "Containing none of the words".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[criteria][negative][display]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Search API support.
    if ($this->moduleHandler->moduleExists('search_api_page')) {
      $search_api_pages = search_api_page_load_multiple();
      $options[0] = t('None');
      foreach ($search_api_pages as $page) {
        $options[$page->id()] = $page->label();
      }
      $form['searchapi'] = [
        '#type' => 'details',
        '#title' => $this->t('Search API'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['searchapi']['page'] = [
        '#type' => 'select',
        '#title' => $this->t('Search API Page to use'),
        '#options' => $options,
        '#default_value' => $this->configuration['searchapi']['page'],
      ];
    }

    // Languages.
    $form['languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Languages'),
      '#description' => $this->t("Select the languages to present as search options in the search block. If none is selected, no selector will be displayed. <strong>Note</strong>: if there's only one language checked, the selector won't be displayed BUT only this language will be searched."),
      '#open' => count(array_filter($this->configuration['languages']['languages'])),
    ];
    $languages = \Drupal::languageManager()->getLanguages();
    $languages_options = [
      'current' => $this->t('- Current language -'),
    ];
    foreach ($languages as $id => $language) {
      $languages_options[$id] = $language->getName();
    }
    $languages_options[Language::LANGCODE_NOT_SPECIFIED] = $this->t('- Not specified -');
    $languages_options[Language::LANGCODE_NOT_APPLICABLE] = $this->t('- Not applicable -');
    $form['languages']['languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Languages'),
      '#description' => $this->t("Note: if <em>- Current language -</em> is selected, this current language won't be displayed twice."),
      '#default_value' => $this->configuration['languages']['languages'],
      '#options' => $languages_options,
    ];
    $form['languages']['selector']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector type'),
      '#options' => [
        'select' => $this->t('Drop-down list'),
        'selectmultiple' => $this->t('Drop-down list with multiple choices'),
        'radios' => $this->t('Radio buttons'),
        'checkboxes' => $this->t('Checkboxes'),
      ],
      '#description' => $this->t('Choose which selector type to use.'),
      '#default_value' => $this->configuration['languages']['selector']['type'],
    ];
    $form['languages']['selector']['label_visibility'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['languages']['selector']['label_visibility'],
    ];
    $form['languages']['selector']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label text'),
      '#default_value' => $this->configuration['languages']['selector']['label'],
      '#description' => $this->t('Enter the label text for the selector. The default value is "Languages".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[languages][selector][label_visibility]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['languages']['any'] = [
      '#type' => 'details',
      '#title' => $this->t('- Any -'),
    ];
    $form['languages']['any']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('- Any language - text'),
      '#default_value' => $this->configuration['languages']['any']['text'],
      '#required' => TRUE,
      '#description' => $this->t('Enter the text for "any language" choice. The default value is "- Any -".'),
    ];
    $form['languages']['any']['restricts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Choosing - Any - restricts the search to the selected languages.'),
      '#default_value' => $this->configuration['languages']['any']['restricts'],
      '#description' => $this->t('If not checked, choosing - Any - will search in all languages.'),
    ];
    $form['languages']['any']['force'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force - Any - to be displayed.'),
      '#default_value' => $this->configuration['languages']['any']['force'],
      '#description' => $this->t('When only one language is selected, the default behaviour is to hide the selector. If you need the - Any - option to be displayed, check this.'),
    ];

    // Custom Paths.
    $form['paths'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom search paths'),
      '#open' => $this->configuration['paths']['list'] != '',
    ];
    $form['paths']['selector']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector type'),
      '#options' => [
        'select' => $this->t('Drop-down list'),
        'radios' => $this->t('Radio buttons'),
      ],
      '#description' => $this->t('Choose which selector type to use.'),
      '#default_value' => $this->configuration['paths']['selector']['type'],
    ];
    $form['paths']['selector']['label_visibility'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['paths']['selector']['label_visibility'],
    ];
    $form['paths']['selector']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label text'),
      '#default_value' => $this->configuration['paths']['selector']['label'],
      '#description' => $this->t('Enter the label text for the selector. The default value is "Customize your search".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[paths][selector][label_visibility]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['paths']['list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => $this->configuration['paths']['list'],
      '#rows' => 3,
      '#description' => $this->t('If you want to use custom search paths, enter them here in the form <em>path</em>|<em>label</em>, one per line (if only one path is specified, the selector will be hidden). The [key] token will be replaced by what is entered in the search box, the [types] token will be replaced by the selected content types machine name(s) and the [terms] token will be replaced by the selected taxonomy term id(s). Ie: mysearch/[key]|My custom search label. The [current_path] token can also be used to use the current URL path of the page being viewed.'),
    ];
    $form['paths']['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arguments separator'),
      '#description' => $this->t('Enter a separator that will be used when multiple content types or taxonomy terms are selected and [types] and/or [terms] tokens are used.'),
      '#default_value' => $this->configuration['paths']['separator'],
      '#size' => 2,
    ];

    // Ordering.
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'custom_search/custom_search.ordering';

    $form['order'] = [
      '#type' => 'details',
      '#title' => $this->t('Elements layout'),
      '#description' => $this->t('Order the form elements as you want them to be displayed. If you put elements in the Popup region, they will only appear when the search field is clicked.'),
      '#open' => TRUE,
    ];
    $form['order']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Element'),
        $this->t('Region'),
        $this->t('Weight'),
      ],
      '#attributes' => [
        'id' => 'elements',
      ],
    ];

    $elements = [
      'search_box' => [
        'label' => $this->t('Search box'),
        'config' => $this->configuration['search_box'],
      ],
      'submit' => [
        'label' => $this->t('Submit button'),
        'config' => $this->configuration['submit'],
      ],
      'content' => [
        'label' => $this->t('Content types'),
        'config' => $this->configuration['content'],
      ],
      'or' => [
        'label' => $this->t('Criteria: Containing any of the words'),
        'config' => $this->configuration['criteria']['or'],
      ],
      'phrase' => [
        'label' => $this->t('Criteria: Containing the phrase'),
        'config' => $this->configuration['criteria']['phrase'],
      ],
      'negative' => [
        'label' => $this->t('Criteria: Containing none of the words'),
        'config' => $this->configuration['criteria']['negative'],
      ],
      'languages' => [
        'label' => $this->t('Languages'),
        'config' => $this->configuration['languages'],
      ],
      'paths' => [
        'label' => $this->t('Custom Path'),
        'config' => $this->configuration['paths'],
      ],
    ];
    if (count($vocabularies)) {
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $elements['voc-' . $vocId] = [
          'label' => $this->t('Taxonomy: @name', ['@name' => $voc->label()]),
          'config' => $this->configuration['taxonomy'][$vocId],
        ];
      }
    }
    uasort($elements, [$this, 'weightsSort']);
    $regions = [
      'block' => $this->t('Block'),
      'popup' => $this->t('Popup'),
    ];

    foreach ($elements as $id => $element) {
      $element_config = $element['config'];
      $regionsElements[$element_config['region']][$id] = $element;
    }

    foreach ($regions as $region => $title) {
      $form['order']['table']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'order-region',
        'subgroup' => 'order-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['order']['table']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'order-weight',
        'subgroup' => 'order-weight-' . $region,
      ];
      $form['order']['table'][$region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['order']['table'][$region]['title'] = [
        '#markup' => $title,
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      $form['order']['table'][$region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($regionsElements[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['order']['table'][$region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No elements in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($regionsElements[$region])) {
        foreach ($regionsElements[$region] as $id => $element) {
          $element_config = $element['config'];
          $form['order']['table'][$id]['#attributes']['class'][] = 'draggable';
          $form['order']['table'][$id]['#weight'] = $element_config['weight'];
          $form['order']['table'][$id]['element'] = ['#markup' => $element['label']];
          $form['order']['table'][$id]['region'] = [
            '#type' => 'select',
            '#title' => $this->t('Region for @title', ['@title' => $element['label']]),
            '#title_display' => 'invisible',
            '#options' => [
              'block' => $this->t('Block'),
              'popup' => $this->t('Popup'),
            ],
            '#default_value' => $region,
            '#attributes' => ['class' => ['order-region', 'order-region-' . $region]],
          ];
          $form['order']['table'][$id]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $element['label']]),
            '#title_display' => 'invisible',
            '#default_value' => $element_config['weight'],
            '#attributes' => ['class' => ['order-weight', 'order-weight-' . $element_config['region']]],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockValidate().
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue(['paths', 'list']) != '') {
      $lines = explode("\n", $form_state->getValue(['paths', 'list']));
      foreach ($lines as $line) {
        if (strpos($line, '|') < 1) {
          $form_state->setErrorByName('list', $this->t('Custom path must be in the form <em>path</em>|<em>label</em>.'));
          break;
        }
      }
    }
    if ($this->moduleHandler->moduleExists('file')) {
      // Handle file uploads.
      $validators = ['file_validate_is_image' => []];

      // Check for a new uploaded logo.
      $file = file_save_upload('settings', $validators, FALSE, 0);
      if (isset($file)) {
        // File upload was attempted.
        if ($file) {
          $directory_path = 'public://custom_search';
          file_prepare_directory($directory_path, FILE_CREATE_DIRECTORY);
          $filename = file_unmanaged_copy($file->getFileUri(), $directory_path);
          $form_state->setValue(['submit', 'image_path'], $filename);
        }
        else {
          // File upload failed.
          $form_state->setErrorByName(
            'image', $this->t('The submit image could not be uploaded.')
                  );
        }
      }
      // If the user provided a path for a logo or favicon file, make sure a
      // file exists at that path.
      if (!$form_state->isValueEmpty(['submit', 'image_path'])) {
        $path = $this->validatePath($form_state->getValue(['submit', 'image_path']));
        if (!$path) {
          $form_state->setErrorByName(
            'image_path', $this->t('The submit image path is invalid.')
          );
        }
      }
    }
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['search_box'] = [
      'label_visibility' => $form_state->getValue([
        'search_box',
        'label_visibility',
      ]),
      'label' => $form_state->getValue([
        'search_box',
        'label',
      ]),
      'placeholder' => $form_state->getValue([
        'search_box',
        'placeholder',
      ]),
      'title' => $form_state->getValue([
        'search_box',
        'title',
      ]),
      'size' => $form_state->getValue([
        'search_box',
        'size',
      ]),
      'max_length' => $form_state->getValue([
        'search_box',
        'max_length',
      ]),
      'weight' => $form_state->getValue([
        'order',
        'table',
        'search_box',
        'weight',
      ]),
      'region' => $form_state->getValue([
        'order',
        'table',
        'search_box',
        'region',
      ]),
    ];

    $this->configuration['submit'] = [
      'text' => $form_state->getValue([
        'submit',
        'text',
      ]),
      'weight' => $form_state->getValue([
        'order',
        'table',
        'submit',
        'weight',
      ]),
      'region' => $form_state->getValue([
        'order',
        'table',
        'submit',
        'region',
      ]),
    ];
    // If the user uploaded a new submit image, save it to a permanent location.
    if ($this->moduleHandler->moduleExists('file')) {
      // If the user entered a path relative to the system files directory for
      // the submit image, store a public:// URI so the theme system can handle
      // it.
      if (!$form_state->isValueEmpty(['submit', 'image_path'])) {
        $this->configuration['submit']['image_path'] = $this->validatePath($form_state->getValue(['submit', 'image_path']));
      }
    }

    $this->configuration['content'] = [
      'page' => $form_state->getValue([
        'content',
        'page',
      ]),
      'types' => $form_state->getValue([
        'content',
        'types',
      ]),
      'other' => $form_state->getValue([
        'content',
        'other',
      ]),
      'selector' => [
        'type' => $form_state->getValue([
          'content',
          'selector',
          'type',
        ]),
        'label_visibility' => $form_state->getValue([
          'content',
          'selector',
          'label_visibility',
        ]),
        'label' => $form_state->getValue([
          'content',
          'selector',
          'label',
        ]),
      ],
      'any' => [
        'text' => $form_state->getValue([
          'content',
          'any',
          'text',
        ]),
        'restricts' => $form_state->getValue([
          'content',
          'any',
          'restricts',
        ]),
        'force' => $form_state->getValue([
          'content',
          'any',
          'force',
        ]),
      ],
      'excluded' => $form_state->getValue([
        'content',
        'excluded',
      ]),
      'weight' => $form_state->getValue([
        'order',
        'table',
        'content',
        'weight',
      ]),
      'region' => $form_state->getValue([
        'order',
        'table',
        'content',
        'region',
      ]),
    ];

    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    if (count($vocabularies)) {
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $this->configuration['taxonomy'][$vocId] = [
          'type' => $form_state->getValue([
            'taxonomy',
            $vocId,
            'type',
          ]),
          'depth' => $form_state->getValue([
            'taxonomy',
            $vocId,
            'depth',
          ]),
          'label_visibility' => $form_state->getValue([
            'taxonomy',
            $vocId,
            'label_visibility',
          ]),
          'label' => $form_state->getValue([
            'taxonomy',
            $vocId,
            'label',
          ]),
          'all_text' => $form_state->getValue([
            'taxonomy',
            $vocId,
            'all_text',
          ]),
          'weight' => $form_state->getValue([
            'order',
            'table',
            'voc-' . $vocId,
            'weight',
          ]),
          'region' => $form_state->getValue([
            'order',
            'table',
            'voc-' . $vocId,
            'region',
          ]),
        ];
      }
    }

    $this->configuration['criteria'] = [
      'or' => [
        'display' => $form_state->getValue([
          'criteria',
          'or',
          'display',
        ]),
        'label' => $form_state->getValue([
          'criteria',
          'or',
          'label',
        ]),
        'weight' => $form_state->getValue([
          'order',
          'table',
          'or',
          'weight',
        ]),
        'region' => $form_state->getValue([
          'order',
          'table',
          'or',
          'region',
        ]),
      ],
      'phrase' => [
        'display' => $form_state->getValue([
          'criteria',
          'phrase',
          'display',
        ]),
        'label' => $form_state->getValue([
          'criteria',
          'phrase',
          'label',
        ]),
        'weight' => $form_state->getValue([
          'order',
          'table',
          'phrase',
          'weight',
        ]),
        'region' => $form_state->getValue([
          'order',
          'table',
          'phrase',
          'region',
        ]),
      ],
      'negative' => [
        'display' => $form_state->getValue([
          'criteria',
          'negative',
          'display',
        ]),
        'label' => $form_state->getValue([
          'criteria',
          'negative',
          'label',
        ]),
        'weight' => $form_state->getValue([
          'order',
          'table',
          'negative',
          'weight',
        ]),
        'region' => $form_state->getValue([
          'order',
          'table',
          'negative',
          'region',
        ]),
      ],
    ];

    if ($this->moduleHandler->moduleExists('search_api_page')) {
      $this->configuration['searchapi']['page'] = $form_state->getValue([
        'searchapi',
        'page',
      ]);
    }

    $this->configuration['languages'] = [
      'languages' => $form_state->getValue([
        'languages',
        'languages',
      ]),
      'selector' => [
        'type' => $form_state->getValue([
          'languages',
          'selector',
          'type',
        ]),
        'label_visibility' => $form_state->getValue([
          'languages',
          'selector',
          'label_visibility',
        ]),
        'label' => $form_state->getValue([
          'languages',
          'selector',
          'label',
        ]),
      ],
      'any' => [
        'text' => $form_state->getValue([
          'languages',
          'any',
          'text',
        ]),
        'restricts' => $form_state->getValue([
          'languages',
          'any',
          'restricts',
        ]),
        'force' => $form_state->getValue([
          'languages',
          'any',
          'force',
        ]),
      ],
      'weight' => $form_state->getValue([
        'order',
        'table',
        'languages',
        'weight',
      ]),
      'region' => $form_state->getValue([
        'order',
        'table',
        'languages',
        'region',
      ]),
    ];

    $this->configuration['paths'] = [
      'list' => $form_state->getValue([
        'paths',
        'list',
      ]),
      'selector' => [
        'type' => $form_state->getValue([
          'paths',
          'selector',
          'type',
        ]),
        'label_visibility' => $form_state->getValue([
          'paths',
          'selector',
          'label_visibility',
        ]),
        'label' => $form_state->getValue([
          'paths',
          'selector',
          'label',
        ]),
      ],
      'separator' => $form_state->getValue([
        'paths',
        'separator',
      ]),
      'weight' => $form_state->getValue([
        'order',
        'table',
        'paths',
        'weight',
      ]),
      'region' => $form_state->getValue([
        'order',
        'table',
        'paths',
        'region',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\custom_search\Form\CustomSearchBlockForm', $this->configuration);
  }

  /**
   * Helper function for the form.
   *
   * Attempts to validate normal system paths, paths relative to the public
   * files directory, or stream wrapper URIs. If the given path is any of the
   * above, returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    // Absolute local file paths are invalid.
    if (\Drupal::service('file_system')->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (\Drupal::service('file_system')->uriScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

  /**
   * Helper function for sorting elements in the ordering table.
   *
   * @param mixed $a
   *   The first value to compare.
   * @param mixed $b
   *   The second value to compare.
   *
   * @return int
   *   An integer less than, equal to, or greater than zero if the first
   *   argument is considered to be respectively less than, equal to, or
   *   greater than the second.
   */
  private static function weightsSort($a, $b) {
    $config_a = $a['config'];
    $config_b = $b['config'];
    if ($config_a['weight'] == $config_b['weight']) {
      return 0;
    }
    return ($config_a['weight'] < $config_b['weight']) ? -1 : 1;
  }

}
