<?php

namespace Drupal\node_layout_builder;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node_layout_builder\Helpers\NodeLayoutBuilderHelper;
use Drupal\node_layout_builder\Helpers\NodeLayoutFileHelper;
use Drupal\node_layout_builder\Services\NodeLayoutBuilderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeLayoutBuilderEditor.
 *
 * Methods for handling element data.
 */
class NodeLayoutBuilderEditor {

  use StringTranslationTrait;

  /**
   * The instantiated NodeLayoutBuilderManager class.
   *
   * @var \Drupal\node_layout_builder\Services\NodeLayoutBuilderManager
   */
  public $nodeLayoutBuilderManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(NodeLayoutBuilderManager $node_layout_builder_service, ConfigFactory $config_factory) {
    $this->nodeLayoutBuilderManager = $node_layout_builder_service;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('node_layout_builder.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Applies the layout to an entity build.
   *
   * @param array $build
   *   Build entity.
   *
   * @return array
   *   Entity build
   */
  public function buildView(array &$build) {
    if (!$this->nodeLayoutBuilderManager->isBuilderEnabled()) {
      return $build;
    }

    // Ensure that query arguments affect the cache.
    $build['#cache']['contexts'] = [
      'user.permissions',
      'url.query_args:layout-builder',
      'url.query_args:layout',
    ];

    // Get current node.
    $nid = $build['#node']->id();

    // Import / Export.
    $list_btns_import_export = self::buttonsImportExport($nid);
    // Buttons add first row element.
    $btn_add_section = self::buttonAddNewSection($nid, 0);
    $btn_save_data = self::buttonSaveData($nid);
    // Button submition.
    $btn_save_layout = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<i class="fa fa-save"></i> ' . $this->t('Save Layout')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.save',
        [
          'nid' => $nid,
        ]
      ),
      '#attributes' => [
        'id' => 'layout-editor-save',
        'class' => ['use-ajax', 'btn-nlb'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => NodeLayoutBuilderEditor::modalDialogOptions(),
        'title' => $this->t('Insert new row to builder'),
      ],
    ];

    // Generate layout theme UI.
    $build[] = [
      '#theme' => 'node_layout_builder_ui',
      '#btns_import_export' => $list_btns_import_export,
      '#btn_add_section' => render($btn_add_section),
      '#btn_save_data' => render($btn_save_data),
      '#nid' => $nid,
      '#data' => 'data',
      '#btn_save_layout' => $btn_save_layout,
      '#editable' => 1,
      '#attached' => [
        'library' => [
          'node_layout_builder/node-layout-builder-ui',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Render import export data of element.
   *
   * @param int $nid
   *   NID of entity.
   *
   * @return array
   *   Theme item list.
   */
  public static function buttonsImportExport($nid) {
    $btns_import_export = [];
    $btns_import_export[] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="fa fa-cloud-upload" aria-hidden="true"></span> ' . t('Export')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.data.import',
        ['nid' => $nid]
      ),
      '#attributes' => [
        'class' => ['use-ajax', 'btn-nlb'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Import builder'),
      ],
    ];
    $btns_import_export[] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="fa fa-cloud-download" aria-hidden="true"></span> ' . t('Import')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.data.export',
        ['nid' => $nid]
      ),
      '#attributes' => [
        'class' => ['use-ajax', 'btn-nlb'],
        'title' => t('Export builder'),
      ],
    ];

    return [
      '#theme' => 'item_list',
      '#items' => $btns_import_export,
      '#title' => NULL,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['btn-import-export-links'],
      ],
    ];
  }

  /**
   * Render Add new section.
   *
   * @param int $nid
   *   NID entity.
   * @param int $id_element
   *   ID element.
   *
   * @return array
   *   Theme item list.
   */
  public static function buttonAddNewSection($nid, $id_element) {
    return [
      '#type' => 'link',
      '#title' => Markup::create(''),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'section',
          'parent' => 0,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'btn-add-section',
          'btn',
          'btn-default',
          'glyphicon',
          'glyphicon-plus-sign',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => NodeLayoutBuilderEditor::modalDialogOptions(),
        'title' => t('Insert new section'),
      ],
    ];
  }

  /**
   * Render render save data.
   *
   * @param int $nid
   *   NID entity.
   *
   * @return array
   *   Theme item list.
   */
  public static function buttonSaveData($nid) {
    return [
      '#type' => 'link',
      '#title' => Markup::create(t('Save')),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.save',
        ['nid' => $nid]
      ),
      '#attributes' => [
        'class' => ['use-ajax', 'btn-save-data', 'btn btn-primary fa fa-save'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => NodeLayoutBuilderEditor::modalDialogOptions(),
        'title' => t('Insert new section'),
      ],
    ];
  }

  /**
   * Get Default Options of modal dialog.
   *
   * @return string
   *   Options modal.
   */
  public static function modalDialogOptions() {
    return json_encode([
      'width' => '70%',
      'height' => 'auto',
      'maxWidth' => '900',
      'resizable' => TRUE,
      'modal' => TRUE,
      'top' => '10%',
    ]);
  }

  /**
   * Get list skins for node layout builder editor.
   *
   * @return array
   *   List skins
   */
  public static function listSkins() {
    return [
      0 => 'default',
      1 => 'grey',
      2 => 'red',
      3 => 'yellow',
      4 => 'green',
      5 => 'orange',
    ];
  }

  /**
   * Get form by type element.
   *
   * @param array $element
   *   Element data.
   * @param string $type
   *   Type element.
   *
   * @return mixed
   *   Render element by type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getFormByTypeElement(array $element, $type) {
    $values = isset($element['#settings']) ? $element['#settings'] : [];

    $form = self::attributesAndStylesForm($values, $element, $type);

    switch ($type) {
      case 'section':
        break;

      case 'column':
        $grid_options = [];
        for ($i = 1; $i <= 12; $i++) {
          $grid_options[$i] = $i . '/' . 12;
        }
        $form['settings']['column']['grid'] = [
          '#type' => 'select',
          '#title' => t('Grid size')->render(),
          '#description' => t('Select size of column')->render(),
          '#options' => $grid_options,
          '#default_value' => isset($values['column']['grid']) ? $values['column']['grid'] : 6,
        ];
        break;

      case 'text':
        $form['settings']['text'] = [
          '#type' => 'text_format',
          '#default_value' => isset($values['text']['value']) ? $values['text']['value'] : NodeLayoutBuilderHelper::getLoremText(),
          '#format' => isset($values['text']['format']) ? $values['text']['format'] : filter_default_format(),
        ];
        break;

      case 'image':
        $form['settings']['image_data']['from'] = [
          '#title' => t('Image source'),
          '#type' => 'select',
          '#options' => [1 => t('From disk'), 2 => t('Url')],
          '#attributes' => [
            'id' => 'edit-configue-image-from',
          ],
        ];
        $form['settings']['image_data']['link'] = [
          '#type' => 'textfield',
          '#title' => t('Image link'),
          '#default_value' => isset($values['image_data']['link']) ? $values['image_data']['link'] : '',
          '#states' => [
            'visible' => [
              ':input[id="edit-configue-image-from"]' => ['value' => 2],
            ],
          ],
        ];
        $form['settings']['image_data']['image'] = [
          '#title' => t('Upload image'),
          '#type' => 'managed_file',
          '#description' => t('GIF / JPG / JPEG / PNG formats only'),
          '#upload_validators' => [
            'file_validate_extensions' => ['gif jpg jpeg png'],
            'file_validate_size' => [5000000],
          ],
          '#upload_location' => 'public://node_layout_builder/images/',
          '#default_value' => isset($values['image_data']['image']) ? $values['image_data']['image'] : NULL,
          '#theme' => 'image_widget',
          '#preview_image_style' => 'medium',
          '#states' => [
            'visible' => [
              ':input[id="edit-configue-image-from"]' => ['value' => 1],
            ],
          ],
        ];
        $form['settings']['image_data']['title'] = [
          '#title' => t('Title'),
          '#type' => 'textfield',
          '#default_value' => isset($values['image_data']['title']) ? $values['image_data']['title'] : '',
        ];
        $form['settings']['image_data']['alt'] = [
          '#title' => t('Image alt'),
          '#type' => 'textfield',
          '#default_value' => isset($values['image_data']['alt']) ? $values['image_data']['alt'] : '',
        ];
        $form['settings']['image_data']['responsive'] = [
          '#title' => t('Responsive'),
          '#type' => 'checkbox',
          '#default_value' => isset($values['image_data']['responsive']) ? $values['image_data']['responsive'] : 1,
          '#description' => t('Make image responsive.'),
        ];

        $options = NodeLayoutBuilderHelper::loadImageStyles();
        $form['settings']['image_data']['style'] = [
          '#type' => 'select',
          '#title' => t('Image style'),
          '#default_value' => isset($values['image_data']['style']) ? $values['image_data']['style'] : 0,
          '#options' => $options,
          '#states' => [
            'invisible' => [
              ':input[name="configue[settings][image_data][responsive]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['settings']['image_data']['height'] = [
          '#title' => t('Height'),
          '#type' => 'number',
          '#default_value' => isset($values['image_data']['height']) ? $values['image_data']['height'] : 0,
          '#states' => [
            'invisible' => [
              ':input[name="configue[settings][image_data][responsive]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        break;

      case 'video':
        $form['settings']['video_youtube'] = [
          '#type' => 'details',
          '#title' => t('Youtube'),
          '#group' => 'video_youtube_tabs',
          '#open' => TRUE,
        ];
        $form['settings']['video_youtube']['url'] = [
          '#title' => t('URL video youtube'),
          '#type' => 'textfield',
          '#default_value' => isset($values['video_youtube']['url']) ? $values['video_youtube']['url'] : '',
          '#required' => TRUE,
        ];
        $form['settings']['video_youtube']['width'] = [
          '#title' => t('Width'),
          '#type' => 'number',
          '#default_value' => isset($values['video_youtube']['width']) ? $values['video_youtube']['width'] : 600,
          '#required' => TRUE,
        ];
        $form['settings']['video_youtube']['height'] = [
          '#title' => t('Height'),
          '#type' => 'number',
          '#default_value' => isset($values['video_youtube']['height']) ? $values['video_youtube']['height'] : 300,
          '#required' => TRUE,
        ];
        $form['settings']['video_youtube']['responsive'] = [
          '#title' => t('Responsive'),
          '#type' => 'checkbox',
          '#default_value' => isset($values['video_youtube']['responsive']) ? $values['video_youtube']['responsive'] : 1,
          '#description' => t('Make youtube player responsive.'),
        ];
        $form['settings']['video_youtube']['autoplay'] = [
          '#title' => t('Auto play'),
          '#type' => 'checkbox',
          '#default_value' => isset($values['video_youtube']['autoplay']) ? $values['video_youtube']['autoplay'] : FALSE,
          '#description' => t('Enable / disable autplay of player video.'),
        ];
        break;

      case 'audio':
        $form['settings']['audio'] = [
          '#type' => 'details',
          '#title' => t('Audio'),
          '#group' => 'audio_tabs',
          '#open' => TRUE,
        ];
        $form['settings']['audio']['url'] = [
          '#title' => t('URL'),
          '#type' => 'url',
          '#default_value' => isset($values['audio']['url']) ? $values['audio']['url'] : '',
          '#required' => TRUE,
        ];
        $form['settings']['audio']['responsive'] = [
          '#title' => t('Responsive'),
          '#type' => 'checkbox',
          '#default_value' => isset($values['audio']['responsive']) ? $values['audio']['responsive'] : 0,
          '#description' => t('Make audio player responsive.'),
        ];
        $form['settings']['audio']['width'] = [
          '#title' => t('Width'),
          '#type' => 'number',
          '#default_value' => isset($values['audio']['width']) ? $values['audio']['width'] : 300,
          '#required' => FALSE,
        ];
        break;

      case 'block':
        $theme = NodeLayoutBuilderHelper::getActiveTheme();
        $blocks = NodeLayoutBuilderHelper::getAllBlocksByTheme($theme);
        $form['settings']['block_id'] = [
          '#type' => 'select',
          '#title' => t('Select a block'),
          '#options' => $blocks,
          '#default_value' => isset($values['block_id']) ? $values['block_id'] : NULL,
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        break;

      case 'node':
        $node_default = NULL;
        $nid = !empty($values['node']) ? $values['node'] : NULL;
        if ($nid) {
          $node_default = \Drupal::entityManager()
            ->getStorage('node')
            ->load($nid);
        }
        $form['settings']['node'] = [
          '#type' => 'entity_autocomplete',
          '#title' => t('Node'),
          '#default_value' => $node_default,
          '#target_type' => 'node',
          '#required' => TRUE,
        ];
        $form['settings']['view_mode'] = [
          '#type' => 'select',
          '#title' => t('View mode'),
          '#options' => NodeLayoutBuilderHelper::getEntityViewModes('node'),
          '#default_value' => !empty($values['view_mode']) ? $values['view_mode'] : 'full',
        ];
        $form['settings']['hide_node_title'] = [
          '#type' => 'checkbox',
          '#title' => t('Hide node title'),
          '#default_value' => !empty($values['hide_node_title']) ? $values['hide_node_title'] : FALSE,
        ];
        break;

      case 'field':
        $display = NodeLayoutBuilderHelper::getEntityViewDiplay('node', 'page');
        $fields = NodeLayoutBuilderHelper::getFieldsEntity([], $display);
        if (isset($fields['links'])) {
          unset($fields['links']);
        }
        $fields = array_keys($fields);
        $list = array_combine($fields, $fields);

        $form['settings']['entity_field'] = [
          '#type' => 'select',
          '#title' => t('Select a block'),
          '#options' => $list,
          '#default_value' => isset($values['entity_field']) ? $values['entity_field'] : NULL,
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        $form['settings']['view_mode'] = [
          '#type' => 'select',
          '#title' => t('View mode'),
          '#options' => NodeLayoutBuilderHelper::getEntityViewModes('node'),
          '#default_value' => !empty($values['view_mode']) ? $values['view_mode'] : 'full',
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        break;

      case 'btn':
        $form['settings']['button']['type'] = [
          '#type' => 'select',
          '#title' => t('Type'),
          '#options' => NodeLayoutBuilderHelper::listTypeButtons(),
          '#default_value' => !empty($values['button']['type']) ? $values['button']['type'] : 0,
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        $form['settings']['button']['title'] = [
          '#type' => 'textfield',
          '#title' => t('Title'),
          '#default_value' => isset($values['button']['title']) ? $values['button']['title'] : '',
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        $form['settings']['button']['link'] = [
          '#type' => 'url',
          '#title' => t('Link'),
          '#default_value' => isset($values['button']['link']) ? $values['button']['link'] : '',
          '#attributes' => [
            'class' => ['form-control'],
          ],
        ];
        $form['settings']['button']['is_block_btn'] = [
          '#type' => 'checkbox',
          '#title' => t('Block button'),
          '#default_value' => isset($values['button']['is_block_btn']) ? $values['button']['is_block_btn'] : 0,
          '#attributes' => [
            'class' => ['form-control'],
            'data-toggle' => 'toggle',
          ],
        ];
        break;

      case 'map':
        $form['settings']['map_gps_zoom'] = [
          '#type' => 'number',
          '#title' => t('Zoom'),
          '#default_value' => !empty($values['map_gps_zoom']) ? $values['map_gps_zoom'] : 6,
          '#attributes' => [
            'class' => ['form-control'],
          ],
          '#prefix' => '<div class="container-fluid"><div class="row"><div class="col-md-4">',
          '#suffix' => '</div>',
        ];
        $form['settings']['map_gps_latitude'] = [
          '#type' => 'textfield',
          '#title' => t('Latitude'),
          '#default_value' => !empty($values['map_gps_latitude']) ? $values['map_gps_latitude'] : 48.89787934181869,
          '#attributes' => [
            'class' => ['form-control'],
          ],
          '#prefix' => '<div class="col-md-4">',
          '#suffix' => '</div>',
        ];
        $form['settings']['map_gps_longitude'] = [
          '#type' => 'textfield',
          '#title' => t('Longitude'),
          '#default_value' => !empty($values['map_gps_longitude']) ? $values['map_gps_longitude'] : 2.3526754975318913,
          '#attributes' => [
            'class' => ['form-control'],
          ],
          '#prefix' => '<div class="col-md-4">',
          '#suffix' => '</div></div></div>',
        ];
        break;

    }

    return $form;
  }

  /**
   * Get fields attributes and styles for form element.
   *
   * @param array $values
   *   Values form.
   * @param array $element
   *   Data element.
   * @param string $type
   *   Type element.
   *
   * @return mixed
   *   Fields form.
   */
  public static function attributesAndStylesForm(array $values, array $element, string $type) {

    $form['vertical_tabs'] = [
      '#type' => 'horizontal_tabs',
      '#parents' => ['vertical_tabs'],
    ];

    // Settings.
    $form['settings'] = [
      '#type' => 'container',
    ];

    $form['attributes'] = [
      '#type' => 'container',
    ];
    $form['attributes']['id'] = [
      '#type' => 'textfield',
      '#title' => t('ID')->render(),
      '#default_value' => isset($element['#attributes']['id']) ? $element['#attributes']['id'] : '',
      '#description' => t('Enter html ID for element wrapper.'),
    ];
    $form['attributes']['class'] = [
      '#type' => 'textfield',
      '#title' => t('Extra class name')->render(),
      '#default_value' => isset($element['#attributes']['container']['class']) ? $element['#attributes']['container']['class'] : '',
      '#description' => t('Enter html class for element wrapper.'),
    ];

    // Styles.
    $form['styles'] = [
      '#type' => 'container',
    ];
    $form['styles']['styles_tabs'] = [
      '#type' => 'horizontal_tabs',
      '#parents' => ['styles_tabs'],
    ];

    // Dimensions (width / height).
    $form['styles']['dimensions'] = [
      '#type' => 'details',
      '#title' => t('Dimensions'),
      '#group' => 'styles_tabs',
      '#open' => FALSE,
    ];

    if (isset($element['#styles']['dimensions']['height'])) {
      $height = $element['#styles']['dimensions']['height'];
    }
    else {
      if ($type == 'map') {
        $height = 300;
      }
      else {
        $height = 0;
      }
    }

    $form['styles']['dimensions']['height'] = [
      '#type' => 'number',
      '#title' => t('Height'),
      '#default_value' => $height,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];

    // Font.
    $form['styles']['font'] = [
      '#type' => 'details',
      '#title' => t('Font'),
      '#group' => 'styles_tabs',
      '#open' => FALSE,
    ];
    $form['styles']['font']['size'] = [
      '#type' => 'number',
      '#title' => t('Font size'),
      '#default_value' => isset($element['#styles']['font']['size']) ? $element['#styles']['font']['size'] : NULL,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];
    $form['styles']['font']['color'] = [
      '#type' => 'color',
      '#title' => t('Font color'),
      '#default_value' => isset($element['#styles']['font']['color']) ? $element['#styles']['font']['color'] : NULL,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];

    // Styles / Background.
    $form['styles']['background'] = [
      '#type' => 'details',
      '#title' => t('Background'),
      '#group' => 'styles_tabs',
      '#open' => FALSE,
    ];
    $form['styles']['background']['bg_enabled'] = [
      '#title' => t('Use background color'),
      '#type' => 'checkbox',
      '#default_value' => isset($element['#styles']['background']['bg_enabled']) ? $element['#styles']['background']['bg_enabled'] : 0,
    ];
    $form['styles']['background']['color'] = [
      '#title' => t('Background color'),
      '#type' => 'color',
      '#default_value' => !empty($element['#styles']['background']['color']) ? $element['#styles']['background']['color'] : '#ffffff',
      '#states' => [
        'invisible' => [
          ':input[name="configue[styles][background][bg_enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['styles']['background']['from'] = [
      '#title' => t('Image source'),
      '#type' => 'select',
      '#options' => [1 => t('From disk'), 2 => t('Url')],
      '#attributes' => [
        'id' => 'edit-configue-image-from-bg',
      ],
      '#default_value' => isset($element['#styles']['background']['from']) ? $element['#styles']['background']['from'] : 1,
    ];
    $form['styles']['background']['link'] = [
      '#type' => 'textfield',
      '#title' => t('Image link'),
      '#default_value' => isset($element['#styles']['background']['link']) ? $element['#styles']['background']['link'] : '',
      '#states' => [
        'visible' => [
          ':input[id="edit-configue-image-from-bg"]' => ['value' => 2],
        ],
      ],
    ];
    $form['styles']['background']['image'] = [
      '#type' => 'managed_file',
      '#title' => t('Upload background image'),
      '#description' => t('GIF / JPG / JPEG / PNG formats only'),
      '#upload_validators' => [
        'file_validate_extensions' => ['gif jpg jpeg png'],
        'file_validate_size' => [5000000],
      ],
      '#upload_location' => 'public://node_layout_builder/backgrounds/',
      '#default_value' => isset($element['#styles']['background']['image']) ? $element['#styles']['background']['image'] : NULL,
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
    ];
    $form['styles']['background']['img_style'] = [
      '#type' => 'select',
      '#title' => t('Background style'),
      '#options' => [
        NULL => 'Default',
        'cover' => 'Cover',
        'contain' => 'Contain',
        'repeat' => 'Repeat',
        'no-repeat' => 'No Repeat',
      ],
      '#default_value' => isset($element['#styles']['background']['img_style']) ? $element['#styles']['background']['img_style'] : NULL,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];

    $form['styles']['background']['img_position'] = [
      '#type' => 'select',
      '#title' => t('Background style'),
      '#options' => [
        'left top' => 'Left Top',
        'left center' => 'Left Center',
        'left bottom' => 'Left Bottom',
        'center top' => 'Center Top',
        'center center' => 'Center Center',
        'center bottom' => 'Center Bottom',
        'right top' => 'Right Top',
        'right center' => 'Right Center',
        'right bottom' => 'Right Bottom',
      ],
      '#default_value' => isset($element['#styles']['background']['img_position']) ? $element['#styles']['background']['img_position'] : NULL,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];

    // Styles / Border.
    $form['styles']['border'] = [
      '#type' => 'details',
      '#title' => t('Border'),
      '#group' => 'styles_tabs',
      '#open' => FALSE,
    ];
    $form['styles']['border']['style'] = [
      '#type' => 'select',
      '#title' => t('Border style'),
      '#default_value' => isset($element['#styles']['border']['style']) ? $element['#styles']['border']['style'] : 'none',
      '#options' => NodeLayoutBuilderHelper::borderStyles(),
    ];
    $form['styles']['border']['width'] = [
      '#title' => t('Border width'),
      '#type' => 'range',
      '#min' => 0,
      '#max' => 10,
      '#default_value' => !empty($element['#styles']['border']['width']) ? $element['#styles']['border']['width'] : 0,
      '#states' => [
        'invisible' => [
          ':input[name="configue[styles][border][style]"]' => ['value' => 'none'],
        ],
      ],
    ];
    $form['styles']['border']['color_enabled'] = [
      '#title' => t('Use color'),
      '#type' => 'checkbox',
      '#default_value' => isset($element['#styles']['border']['color_enabled']) ? $element['#styles']['border']['color_enabled'] : 0,
      '#states' => [
        'invisible' => [
          ':input[name="configue[styles][border][style]"]' => ['value' => 'none'],
        ],
      ],
    ];
    $form['styles']['border']['color'] = [
      '#title' => t('Border color'),
      '#type' => 'color',
      '#default_value' => !empty($element['#styles']['border']['color']) ? $element['#styles']['border']['color'] : NULL,
      '#states' => [
        'invisible' => [
          ':input[name="configue[styles][border][style]"]' => ['value' => 'none'],
          ':input[name="configue[styles][border][color_enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Styles / Margin and Padding.
    $form['styles']['margin_padding'] = [
      '#type' => 'details',
      '#title' => t('Margin and Padding'),
      '#group' => 'styles_tabs',
      '#open' => FALSE,
    ];

    $form['styles']['margin_padding']['margin'] = [
      '#type' => 'container',
      '#theme_wrappers' => [],
      '#prefix' => '<div class="styles_margin">',
    ];

    $form['styles']['margin_padding']['margin']['top'] = [
      '#type' => 'number',
      '#title' => 'Margin',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#default_value' => isset($element['#styles']['margin_padding']['margin']['top']) ? $element['#styles']['margin_padding']['margin']['top'] : NULL,
    ];
    $form['styles']['margin_padding']['margin']['right'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['margin']['right']) ? $element['#styles']['margin_padding']['margin']['right'] : NULL,
    ];
    $form['styles']['margin_padding']['margin']['bottom'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['margin']['bottom']) ? $element['#styles']['margin_padding']['margin']['bottom'] : NULL,
    ];
    $form['styles']['margin_padding']['margin']['left'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['margin']['left']) ? $element['#styles']['margin_padding']['margin']['left'] : NULL,
    ];

    $form['styles']['margin_padding']['padding'] = [
      '#type' => 'container',
      '#theme_wrappers' => [],
      '#prefix' => '<div class="styles_padding">',
      '#suffix' => '</div>',
    ];

    $form['styles']['margin_padding']['padding']['top'] = [
      '#title' => 'Padding',
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#default_value' => isset($element['#styles']['margin_padding']['padding']['top']) ? $element['#styles']['margin_padding']['padding']['top'] : NULL,
    ];
    $form['styles']['margin_padding']['padding']['right'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['padding']['right']) ? $element['#styles']['margin_padding']['padding']['right'] : NULL,
    ];
    $form['styles']['margin_padding']['padding']['bottom'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['padding']['bottom']) ? $element['#styles']['margin_padding']['padding']['bottom'] : NULL,
    ];
    $form['styles']['margin_padding']['padding']['left'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => ['form-control'],
      ],
      '#theme_wrappers' => [],
      '#default_value' => isset($element['#styles']['margin_padding']['padding']['left']) ? $element['#styles']['margin_padding']['padding']['left'] : NULL,
    ];

    return $form;
  }

  /**
   * Render buttons actions of element.
   *
   * @param string $type
   *   Type element.
   * @param int $nid
   *   NID entity element.
   * @param string $id_element
   *   ID element.
   * @param string $parent
   *   ID parent.
   *
   * @return array
   *   Theme item list.
   */
  public static function renderBtnActions($type, $nid, $id_element, $parent = 0) {
    $btns_actions = [];

    // Drag and drop button.
    $btns_actions[] = [
      '#type' => '#markup',
      '#markup' => '<span class="btn btn-default icon-move glyphicon glyphicon-move"> <span class="type-name">' . $type . '</span></span>',
    ];

    // Add link button.
    $elements_type = ['section', 'row', 'column'];
    if (in_array($type, $elements_type)) {
      $btns_actions[] = [
        '#type' => 'link',
        '#title' => Markup::create(''),
        '#url' => Url::fromRoute(
          'node_layout_builder.element.type',
          [
            'nid' => $nid,
            'type' => $type,
            'parent' => $parent,
            'id_element' => $id_element,
          ]
        ),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'icon-plus',
            'btn',
            'btn-default',
            'glyphicon',
            'glyphicon-plus',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => self::modalDialogOptions(),
          'title' => t('Insert new element'),
        ],
      ];
    }

    // Update link button.
    $btns_actions[] = [
      '#type' => 'link',
      '#title' => Markup::create(''),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.update',
        [
          'nid' => $nid,
          'type' => $type,
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'btn',
          'btn-default',
          'glyphicon',
          'glyphicon-pencil',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Update element'),
      ],
    ];

    // Duplicate link button.
    $btns_actions[] = [
      '#type' => 'link',
      '#title' => Markup::create(''),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.duplicate',
        [
          'nid' => $nid,
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'btn',
          'btn-default',
          'glyphicon',
          'glyphicon-duplicate',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Duplicate element'),
      ],
    ];

    // Remove link button.
    $btns_actions[] = [
      '#type' => 'link',
      '#title' => Markup::create(''),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.remove',
        [
          'nid' => $nid,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'btn btn-default',
          'glyphicon',
          'glyphicon-trash',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Remove element'),
      ],
    ];

    return [
      '#theme' => 'item_list',
      '#items' => $btns_actions,
      '#title' => NULL,
      '#list_type' => 'ul',
    ];
  }

  /**
   * Render links categories of element.
   *
   * @param int $nid
   *   NID entity element.
   * @param int $parent
   *   Parent element.
   * @param int $id_element
   *   Parent element.
   * @param string $type
   *   Type element.
   *
   * @return array
   *   List categogies element.
   */
  public static function linksCategoriesElements($nid, $parent, $id_element, $type) {
    $categories_elements = [
      'layout' => [],
      'content' => [],
      'drupal' => [],
    ];
    $classes = [
      'section' => '',
      'row' => '',
      'column' => '',
      'other_element' => '',
    ];

    if ($type == 'section') {
      $classes['section'] = 'link-disabled';
      $classes['column'] = 'link-disabled';
      $classes['other_element'] = 'link-disabled';
    }

    if ($type == 'row') {
      $classes['section'] = 'link-disabled';
      $classes['row'] = 'link-disabled';
      $classes['other_element'] = 'link-disabled';
    }

    if ($type == 'column') {
      $classes['section'] = 'link-disabled';
      $classes['row'] = 'link-disabled';
      $classes['column'] = 'link-disabled';
    }

    // Layout.
    $categories_elements['layout']['section'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-minus" aria-hidden="true"></span><span class="name">' . t('Section') . '</span>'
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'section',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['section'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert row element'),
      ],
    ];
    $categories_elements['layout']['row'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-align-justify" aria-hidden="true"></span><span class="name">' . t('Row') . '</span>'
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'row',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['row'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert row element'),
      ],
    ];
    $categories_elements['layout']['column'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-th-large" aria-hidden="true"></span><span class="name">' . t('Column') . '</span>'
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'column',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['column'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert column element'),
      ],
    ];

    // Content.
    $categories_elements['content']['text'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-text-size" aria-hidden="true"></span>' . t('Text')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'text',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert text element'),
      ],
    ];
    $categories_elements['content']['image'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-picture" aria-hidden="true"></span>' . t('Image')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'image',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert image element'),
      ],
    ];
    $categories_elements['content']['video'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-facetime-video" aria-hidden="true"></span>' . t('video')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'video',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert video element'),
      ],
    ];
    $categories_elements['content']['audio'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-music" aria-hidden="true"></span>' . t('audio')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'audio',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert audio element'),
      ],
    ];
    $categories_elements['content']['map'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span>' . t('Map')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'map',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert audio element'),
      ],
    ];
    $categories_elements['content']['btn'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-tint" aria-hidden="true"></span>' . t('Button')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'btn',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert audio element'),
      ],
    ];
    $categories_elements['content']['link'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-link" aria-hidden="true"></span>' . t('Link')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'link',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert audio element'),
      ],
    ];

    // Drupal.
    $categories_elements['drupal']['block'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-inbox" aria-hidden="true"></span>' . t('block')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'block',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert block element'),
      ],
    ];
    $categories_elements['drupal']['node'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-leaf" aria-hidden="true"></span>' . t('node')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'node',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert node element'),
      ],
    ];
    $categories_elements['drupal']['field'] = [
      '#type' => 'link',
      '#title' => Markup::create(
        '<span class="glyphicon glyphicon-magnet" aria-hidden="true"></span>' . t('Node fields')
      ),
      '#url' => Url::fromRoute(
        'node_layout_builder.element.add',
        [
          'nid' => $nid,
          'type' => 'field',
          'parent' => $parent,
          'id_element' => $id_element,
        ]
      ),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'icon-plus',
          'element-categoy',
          $classes['other_element'],
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => self::modalDialogOptions(),
        'title' => t('Insert field element'),
      ],
    ];

    // Lists.
    $layout = [
      '#theme' => 'item_list',
      '#items' => $categories_elements['layout'],
      '#title' => NULL,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['categories-element-list'],
      ],
    ];
    $content = [
      '#theme' => 'item_list',
      '#items' => $categories_elements['content'],
      '#title' => NULL,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['categories-element-list'],
      ],
    ];
    $drupal = [
      '#theme' => 'item_list',
      '#items' => $categories_elements['drupal'],
      '#title' => NULL,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['categories-element-list'],
      ],
    ];

    return [
      'layout' => $layout,
      'content' => $content,
      'drupal' => $drupal,
    ];
  }

  /**
   * Render recursively children of element.
   *
   * @param array $children
   *   Children of element.
   * @param int $nid
   *   NID entity element.
   *
   * @return string
   *   Theme children element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function renderChildrenRecursive(array $children, $nid) {
    $children_output = '';

    if (count($children) > 0) {
      foreach ($children as $key => $child) {
        $id_element_child = $key;

        $children_data = NodeLayoutBuilderHelper::renderElementByType($child['#type'], $child['#data']);

        if (isset($child['#children'])) {
          $children_data .= self::renderChildrenRecursive($child['#children'], $nid);
        }

        if ($child['#type'] == 'section') {
          $class = '';
          $tag_element = 'section';
        }
        else {
          $class = 'element ';
          $tag_element = 'div';
          if ($child['#type'] == 'column') {
            $class .= 'col-md-' . $child['#data']['column']['grid'] . ' ';
          }
        }

        $styles_element = NodeLayoutBuilderStyle::getStyles($child['#styles']);

        $children_theme = [
          '#theme' => 'node_layout_builder_element',
          '#btns_actions' => self::renderBtnActions($child['#type'], $nid, $id_element_child, $child['#parent']),
          '#nid' => $nid,
          '#type' => $child['#type'],
          '#id_element' => $id_element_child,
          '#parent' => $child['#parent'],
          '#settings' => $child['#data'],
          '#styles' => $styles_element,
          '#content_element' => $children_data,
          '#editable' => 1,
        ];

        $prefix = '<' . $tag_element . ' class="updated ' . $class . ' ' . $child['#type'] . ' ' . $child['#attributes']['container']['class'] . '" id="' . $id_element_child . '" data-id="' . $id_element_child . '" data-parent="' . $child['#parent'] . '" data-type="nlb_' . $child['#type'] . '" style="' . $styles_element . '">';
        $suffix = '</' . $tag_element . '>';

        $children_output .= $prefix . render($children_theme) . $suffix;
      }
    }

    return $children_output;
  }

  /**
   * Render theme element.
   *
   * @return mixed|null
   *   Render theme element.
   */
  public static function renderElementTpl($values) {
    if ($values['type_element'] == 'section') {
      $class = '';
      $tag_element = 'section';
    }
    else {
      $class = 'element ';
      $tag_element = 'div';
      if ($values['type_element'] == 'column') {
        $class .= 'col-md-' . $values['settings']['column']['grid'] . ' ';
      }
    }

    $styles_element = NodeLayoutBuilderStyle::getStyles($values['styles']);

    $element_tpl = [
      '#theme' => 'node_layout_builder_element',
      '#btns_actions' => self::renderBtnActions($values['type_element'], $values['nid'], $values['id_element'], $values['parent']),
      '#nid' => $values['nid'],
      '#type' => $values['type_element'],
      '#id_element' => $values['id_element'],
      '#parent' => $values['parent'],
      '#settings' => $values['settings'],
      '#styles' => $styles_element,
      '#content_element' => render($values['children']),
      '#editable' => $values['editable'],
      '#class' => $class,
    ];

    $prefix = '<' . $tag_element . ' class="' . $class . ' ' . $values['type_element'] . '" id="' . $values['id_element'] . '" data-id="' . $values['id_element'] . '" data-parent="' . $values['parent'] . '" data-type="nlb_' . $values['type_element'] . '" style="' . $styles_element . '">';
    $suffix = '</' . $tag_element . '>';

    return $prefix . render($element_tpl) . $suffix;
  }

  /**
   * Render recursively data and children of each element.
   *
   * @param int $nid
   *   NID entity.
   * @param array $data
   *   Data element entity.
   * @param bool $editable
   *   Boolean to check if editable or not.
   *
   * @return string
   *   Theme element and children elements.
   */
  public function recursive($nid, array $data, $editable) {
    $full_output = '';
    $child_output = '';

    if ($data) {
      foreach ($data as $key => $element) {
        $type = $element['#type'];
        $parent = $element['#parent'];
        $fields_values = $element['#data'];
        if (isset($element['#children'])) {
          $children = $element['#children'];
          $child_output .= $this->recursive($nid, $children, $editable);
        }

        $values = [
          'nid' => $nid,
          'type_element' => $type,
          'id_element' => $key,
          'fields_values' => $fields_values,
          'parent' => $parent,
          'settings' => $fields_values,
          'children' => $child_output,
          'editable' => $editable,
          'attributes' => $element['#attributes'],
          'styles' => $element['#styles'],
        ];

        $full_output .= self::renderElementTpl($values);
        $child_output = '';
      }
    }

    return $full_output;
  }

  /**
   * Insert, update and delete data of element, and then remove data from cache.
   *
   * @param int $nid
   *   NID entity.
   * @param int $uuid
   *   UUID entity.
   * @param array $data
   *   Data entity element.
   */
  public static function saveElementEntity($nid, $uuid, array $data) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('node_layout_builder')
      ->loadByProperties(['entity_id' => $nid]);

    if (!empty($entities)) {
      $entity = reset($entities);
      $old_data = $entity->get('data')->getValue()[0];

      if (count($data) > 0) {
        // Update data element.
        $entity->set('uuid', $uuid);
        $entity->set('data', $data);
        $entity->save();

        // Replace image background or image element.
        if (is_array($old_data)) {
          if (count($old_data) > 0) {
            NodeLayoutFileHelper::saveFileImgElementRecursively($data, $old_data);
            NodeLayoutFileHelper::saveFileImgBgElementRecursively($data, $old_data);
          }
        }
      }
      else {
        $entity->delete();
        // Save files recursively.
        if (is_array($old_data)) {
          if (count($old_data) > 0) {
            NodeLayoutFileHelper::saveFileImgRecursively($old_data, 'delete');
            NodeLayoutFileHelper::saveFileImgBgRecursively($old_data, 'delete');
          }
        }
      }

    }
    else {
      // Save the layout data of node.
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node_layout_builder')
        ->create([
          'uuid' => $uuid,
          'entity_type' => 'node',
          'entity_id' => $nid,
          'vid' => 0,
          'data' => $data,
        ]);
      $entity->save();

      // Save files recursively.
      NodeLayoutFileHelper::saveFileImgRecursively($data, 'create');
      NodeLayoutFileHelper::saveFileImgBgRecursively($data, 'create');
    }

    // Remove Cache of data node.
    NodeLayoutBuilderHelper::deletCache($nid);

    // Flush clear cache for node.
    \Drupal::entityManager()->getViewBuilder('node')->resetCache();
  }

}
