<?php

namespace Drupal\styled_google_map\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use geoPHP;
use Drupal\styled_google_map\StyledGoogleMapInterface;

/**
 * Views area StyledGoogleMapStyle handler.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "styled_google_map",
 *   title = @Translation("Styled Google Map"),
 *   help = @Translation("Displays geofield values on the Google Map with
 *   styles."), theme = "views_view_table", display_types = {"normal"}
 * )
 */
class StyledGoogleMapStyle extends StylePluginBase {
  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Should field labels be enabled by default.
   *
   * @var bool
   */
  protected $defaultFieldLabels = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Default main options.
    $options['main'] = [
      'contains' => [
        'styled_google_map_gesture_handling' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_GESTURE],
        'styled_google_map_view_active_pin' => ['default' => ''],
        'styled_google_map_view_height' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_WIDTH],
        'styled_google_map_view_width' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEIGHT],
        'styled_google_map_view_style' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_STYLE],
        'styled_google_map_view_zoom_default' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ZOOM],
        'styled_google_map_view_zoom_max' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MAX_ZOOM],
        'styled_google_map_view_zoom_min' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MIN_ZOOM],
        'styled_google_map_view_maptype' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MAP_TYPE],
        'styled_google_map_view_maptypecontrol' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MAP_TYPE_CONTROL],
        'styled_google_map_view_scalecontrol' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SCALE_CONTROL],
        'styled_google_map_view_rotatecontrol' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ROTATE_CONTROL],
        'styled_google_map_view_draggable' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_DRAGGABLE],
        'styled_google_map_view_mobile_draggable' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MOBILE_DRAGGABLE],
        'styled_google_map_view_zoomcontrol' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ZOOM_CONTROL],
        'styled_google_map_view_streetviewcontrol' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_STREET_VIEW_CONTROL],
        'styled_google_map_default_map_center' => ['default' => ['lat' => 0, 'lon' => 90]],
      ],
    ];
    // Default popup options.
    $options['popup'] = [
      'contains' => [
        'open_event' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_OPEN_EVENT],
        'styled_google_map_view_shadow_style' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SHADOW_STYLE],
        'styled_google_map_view_padding' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_PADDING],
        'styled_google_map_view_border_radius' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_BORDER_RADIUS],
        'styled_google_map_view_border_width' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_BORDER_WIDTH],
        'styled_google_map_view_border_color' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_BORDER_COLOR],
        'styled_google_map_view_background_color' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_BACKGROUND_COLOR],
        'styled_google_map_view_min_width' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MIN_WIDTH],
        'styled_google_map_view_max_width' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MAX_WIDTH],
        'styled_google_map_view_min_height' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MIN_HEIGHT],
        'styled_google_map_view_max_height' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_MAX_HEIGHT],
        'styled_google_map_view_auto_close' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_AUTO_CLOSE],
        'styled_google_map_view_arrow_size' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_SIZE],
        'styled_google_map_view_arrow_position' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_POSITION],
        'styled_google_map_view_arrow_style' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_STYLE],
        'styled_google_map_view_disable_auto_pan' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_DISABLE_AUTO_PAN],
        'styled_google_map_view_hide_close_button' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HIDE_CLOSE_BUTTON],
        'styled_google_map_view_disable_animation' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_DISABLE_ANIMATION],
      ],
    ];
    // Default popup classes options.
    $options['popup_classes'] = [
      'contains' => [
        'styled_google_map_view_background_class' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_BACKGROUND_CLASS],
        'styled_google_map_view_content_container_class' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CONTENT_CONTAINER_CLASS],
        'styled_google_map_view_arrow_class' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_CLASS],
        'styled_google_map_view_arrow_outer_class' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_OUTER_CLASS],
        'styled_google_map_view_arrow_inner_class' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_ARROW_INNER_CLASS],
      ],
    ];
    // Default cluster settings.
    $options['cluster_settings'] = [
      'contains' => [
        'cluster_enabled' => ['default' => 0],
        'pin_image' => ['default' => ''],
        'text_color' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CLUSTER_TEXT_COLOR],
        'height' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CLUSTER_HEIGHT],
        'width' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CLUSTER_WIDTH],
        'text_size' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CLUSTER_TEXT_SIZE],
        'min_size' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_CLUSTER_MIN_SIZE],
      ],
    ];
    // Default spider settings.
    $options['spider_settings'] = [
      'contains' => [
        'spider_enabled' => ['default' => 0],
        'pin_image' => ['default' => ''],
        'markers_wont_move' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_MARKERS_WONT_MOVE],
        'markers_wont_hide' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_MARKERS_WONT_HIDE],
        'basic_format_events' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_BASIC_FORMAT_EVENTS],
        'keep_spiderfied' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_KEEP_SPIDERFIED],
        'nearby_distance' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_NEARBY_DISTANCE],
        'circle_spiral_switchover' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_CIRCLE_SPIRAL_SWITCHOVER],
        'leg_weight' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_SPIDERFIER_LEG_WEIGHT],
      ],
    ];
    // Default heat map settings.
    $options['heatmap_settings'] = [
      'contains' => [
        'heatmap_enabled' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_ENABLED],
        'dissipating' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_DISSIPATING],
        'gradient' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_GRADIENT],
        'maxIntensity' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_MAX_INTENSITY],
        'opacity' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_OPACITY],
        'radius' => ['default' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_DEFAULT_HEATMAP_RADIUS],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $handlers = $this->displayHandler->getHandlers('field');
    $data_source_options = ['' => $this->t('-- Choose the field --')];
    $pin_source_options = ['' => $this->t('-- Choose the field --')];
    $source_options = ['' => $this->t('-- Choose the field --')];
    /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handle */
    foreach ($handlers as $key => $handle) {
      // Get all location sources.
      if (!empty($handle->options['type']) && $handle->options['type'] == 'geofield_default') {
        $data_source_options[$key] = $handle->adminLabel();
      }
      // Get all pin sources.
      if (!empty($handle->options['type']) && $handle->options['type'] == 'image') {
        $pin_source_options[$key] = $handle->adminLabel();
      }
      // Get all popup sources.
      $source_options[$key] = $handle->adminLabel();
    }

    $form['data_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains geodata?'),
      '#description' => $this->t('Needs to be a geofield.'),
      '#required' => TRUE,
      '#options' => $data_source_options,
      '#default_value' => $this->options['data_source'] ? $this->options['data_source'] : NULL,
    ];
    $form['pin_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains the pin image?'),
      '#description' => $this->t('Needs to be an image field.'),
      '#options' => $pin_source_options,
      '#default_value' => $this->options['pin_source'] ? $this->options['pin_source'] : NULL,
    ];
    $form['default_pin_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default pin image'),
      '#default_value' => $this->options['default_pin_source'],
      '#description' => $this->t('Also you can have a default pin image for all the locations'),
    ];
    $form['popup_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains the popup text?'),
      '#description' => $this->t('Can be a field or rendered entity field.'),
      '#options' => $source_options,
      '#default_value' => $this->options['popup_source'] ? $this->options['popup_source'] : NULL,
    ];
    $form['marker_label'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains the marker label?'),
      '#description' => $this->t('Can be a field or rendered entity field.'),
      '#options' => $source_options,
      '#default_value' => $this->options['marker_label'] ? $this->options['marker_label'] : NULL,
    ];
    $form['category_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains the category?'),
      '#description' => $this->t('This will be used to have a class wrapper around the bubble to allow different styling per category.'),
      '#options' => $source_options,
      '#default_value' => $this->options['category_source'] ? $this->options['category_source'] : NULL,
    ];
    $form['main'] = [
      '#type' => 'details',
      '#title' => $this->t('Map Options'),
    ];
    $form['main']['styled_google_map_view_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#size' => '30',
      '#description' => $this->t('This field determines the height of the styled Google map'),
      '#default_value' => $this->options['main']['styled_google_map_view_height'],
    ];
    $form['main']['styled_google_map_view_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#size' => '30',
      '#description' => $this->t('This field determines how width the styled Google map'),
      '#default_value' => $this->options['main']['styled_google_map_view_width'],
    ];
    $form['main']['styled_google_map_view_style'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Style'),
      '#description' => $this->t('The style of the map'),
      '#default_value' => $this->options['main']['styled_google_map_view_style'],
    ];
    $form['main']['styled_google_map_view_active_pin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Active pin image'),
      '#default_value' => $this->options['main']['styled_google_map_view_active_pin'],
    ];
    $form['main']['styled_google_map_view_maptype'] = [
      '#type' => 'select',
      '#options' => [
        'ROADMAP' => $this->t('ROADMAP'),
        'SATELLITE' => $this->t('SATELLITE'),
        'HYBRID' => $this->t('HYBRID'),
        'TERRAIN' => $this->t('TERRAIN'),
      ],
      '#title' => $this->t('Map type'),
      '#default_value' => $this->options['main']['styled_google_map_view_maptype'],
      '#required' => TRUE,
    ];
    $form['main']['styled_google_map_gesture_handling'] = [
      '#type' => 'select',
      '#title' => $this->t('Gesture handling'),
      '#description' => $this->t('This setting controls how the API handles gestures on the map. See more <a href="@href">here</a>',
        [
          '@href' => 'https://developers.google.com/maps/documentation/javascript/reference/map#MapOptions.gestureHandling',
        ]
      ),
      '#options' => [
        'cooperative' => $this->t('Scroll events with a ctrl key or ⌘ key pressed zoom the map.'),
        'greedy' => $this->t('All touch gestures and scroll events pan or zoom the map.'),
        'none' => $this->t('The map cannot be panned or zoomed by user gestures.'),
        'auto' => $this->t('(default) Gesture handling is either cooperative or greedy'),
      ],
      '#default_value' => $this->options['main']['styled_google_map_gesture_handling'],
    ];
    $form['main']['styled_google_map_view_zoom_default'] = [
      '#type' => 'select',
      '#options' => range(1, 35),
      '#title' => $this->t('Default zoom level'),
      '#default_value' => $this->options['main']['styled_google_map_view_zoom_default'],
      '#description' => $this->t('Should be between the Min and Max zoom level.
        This will generally not working as fitbounds will try to fit all pins on the map.'),
      '#required' => TRUE,
    ];
    $form['main']['styled_google_map_view_zoom_max'] = [
      '#type' => 'select',
      '#options' => range(1, 35),
      '#title' => $this->t('Max zoom level'),
      '#default_value' => $this->options['main']['styled_google_map_view_zoom_max'],
      '#description' => $this->t('Should be greater then the Min zoom level.'),
      '#required' => TRUE,
    ];
    $form['main']['styled_google_map_view_zoom_min'] = [
      '#type' => 'select',
      '#options' => range(1, 35),
      '#title' => $this->t('Min zoom level'),
      '#default_value' => $this->options['main']['styled_google_map_view_zoom_min'],
      '#description' => $this->t('Should be smaller then the Max zoom level.'),
      '#required' => TRUE,
    ];
    $form['main']['styled_google_map_view_maptypecontrol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Map Type control'),
      '#default_value' => $this->options['main']['styled_google_map_view_maptypecontrol'],
    ];
    $form['main']['styled_google_map_view_scalecontrol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable scale control'),
      '#default_value' => $this->options['main']['styled_google_map_view_scalecontrol'],
    ];
    $form['main']['styled_google_map_view_rotatecontrol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable rotate control'),
      '#default_value' => $this->options['main']['styled_google_map_view_rotatecontrol'],
    ];
    $form['main']['styled_google_map_view_draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable dragging'),
      '#default_value' => $this->options['main']['styled_google_map_view_draggable'],
    ];
    $form['main']['styled_google_map_view_mobile_draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable mobile dragging'),
      '#description' => $this->t('Sometimes when the map covers big part of touch device screen draggable feature can cause inability to scroll the page'),
      '#default_value' => $this->options['main']['styled_google_map_view_mobile_draggable'],
    ];
    $form['main']['styled_google_map_view_streetviewcontrol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable street view control'),
      '#default_value' => $this->options['main']['styled_google_map_view_streetviewcontrol'],
    ];
    $form['main']['styled_google_map_view_zoomcontrol'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable zoom control'),
      '#default_value' => $this->options['main']['styled_google_map_view_zoomcontrol'],
    ];
    $form['main']['styled_google_map_default_map_center'] = [
      '#type' => 'geofield_latlon',
      '#title' => $this->t('Default map center coordinates'),
      '#default_value' => $this->options['main']['styled_google_map_default_map_center'],
    ];
    $form['cluster_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Cluster settings'),
    ];
    $form['cluster_settings']['cluster_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable clustering'),
      '#default_value' => $this->options['cluster_settings']['cluster_enabled'],
    ];
    $form['cluster_settings']['pin_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cluster pin image'),
      '#default_value' => $this->options['cluster_settings']['pin_image'],
    ];
    $form['cluster_settings']['text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#default_value' => $this->options['cluster_settings']['text_color'],
    ];
    $form['cluster_settings']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cluster image height'),
      '#default_value' => $this->options['cluster_settings']['height'],
      '#suffix' => $this->t('pixels'),
    ];
    $form['cluster_settings']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cluster image width'),
      '#default_value' => $this->options['cluster_settings']['width'],
      '#suffix' => $this->t('pixels'),
    ];
    $form['cluster_settings']['text_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text size'),
      '#default_value' => $this->options['cluster_settings']['text_size'],
    ];
    $form['cluster_settings']['min_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum cluster size'),
      '#default_value' => $this->options['cluster_settings']['min_size'],
      '#description' => $this->t('The minimum number of pins to be grouped in a cluster.'),
    ];
    $form['spider_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Spider settings'),
    ];
    $form['spider_settings']['spider_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Spider'),
      '#default_value' => $this->options['spider_settings']['spider_enabled'],
    ];
    $form['spider_settings']['markers_wont_move'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not move markers'),
      '#default_value' => $this->options['spider_settings']['markers_wont_move'],
      '#description' => $this->t('Spedir option from config: markersWontMove'),
    ];
    $form['spider_settings']['markers_wont_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not hide markers'),
      '#default_value' => $this->options['spider_settings']['markers_wont_hide'],
      '#description' => $this->t('Spedir option from config: markersWontHide'),
    ];
    $form['spider_settings']['basic_format_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Listen to basic format events'),
      '#default_value' => $this->options['spider_settings']['basic_format_events'],
      '#description' => $this->t('Spedir option from config: basicFormatEvents'),
    ];
    $form['spider_settings']['pin_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spider pin image'),
      '#default_value' => $this->options['spider_settings']['pin_image'],
    ];
    $form['spider_settings']['keep_spiderfied'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep markers spiderfied when clicked'),
      '#default_value' => $this->options['spider_settings']['keep_spiderfied'],
    ];
    $form['spider_settings']['nearby_distance'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The pixel radius within which a marker is considered to be overlapping a clicked marker'),
      '#default_value' => $this->options['spider_settings']['nearby_distance'],
    ];
    $form['spider_settings']['circle_spiral_switchover'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The lowest number of markers that will be fanned out into a spiral instead of a circle'),
      '#description' => $this->t('Set this to 0 to always get spirals, or "Infinity" for all circles.'),
      '#default_value' => $this->options['spider_settings']['circle_spiral_switchover'],
    ];
    $form['spider_settings']['leg_weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The thickness of the lines joining spiderfied markers to their original locations'),
      '#default_value' => $this->options['spider_settings']['leg_weight'],
    ];
    $form['heatmap_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Heat map settings'),
    ];
    $form['heatmap_settings']['heatmap_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable heat map layer'),
      '#default_value' => $this->options['heatmap_settings']['heatmap_enabled'],
    ];
    $form['heatmap_settings']['data_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field contains heat map data?'),
      '#description' => $this->t('Needs to be a geofield.'),
      '#options' => $data_source_options,
      '#default_value' => $this->options['heatmap_settings']['data_source'] ? $this->options['heatmap_settings']['data_source'] : NULL,
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['heatmap_settings']['dissipating'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dissipating'),
      '#description' => $this->t('Specifies whether heatmaps dissipate on zoom. By default, the radius of influence of a data point is specified by the radius option only. When dissipating is disabled, the radius option is interpreted as a radius at zoom level 0.'),
      '#default_value' => $this->options['heatmap_settings']['dissipating'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['heatmap_settings']['gradient'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Gradient'),
      '#description' => $this->t('The color gradient of the heatmap, specified as an array of CSS color strings. All CSS3 colors are supported except for extended named colors. Write one colour per line.'),
      '#default_value' => $this->options['heatmap_settings']['gradient'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['heatmap_settings']['opacity'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#title' => $this->t('Opacity'),
      '#description' => $this->t('The opacity of the heatmap, expressed as a number between 0 and 1. Defaults to 0.6.'),
      '#default_value' => $this->options['heatmap_settings']['opacity'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['heatmap_settings']['radius'] = [
      '#type' => 'number',
      '#title' => $this->t('Radius'),
      '#description' => $this->t('The radius of influence for each data point, in pixels.'),
      '#default_value' => $this->options['heatmap_settings']['radius'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['heatmap_settings']['maxIntensity'] = [
      '#type' => 'number',
      '#title' => $this->t('Max intensity'),
      '#description' => $this->t('The maximum intensity of the heatmap. By default, heatmap colors are dynamically scaled according to the greatest concentration of points at any particular pixel on the map. This property allows you to specify a fixed maximum.'),
      '#default_value' => $this->options['heatmap_settings']['maxIntensity'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[heatmap_settings][heatmap_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['popup'] = [
      '#type' => 'details',
      '#title' => $this->t('Popup Styling'),
      '#description' => $this->t('All settings for the popup exposed by the library. If you want more flexibility in your the styling of the popup. You can use the CSS defined classes'),
    ];
    $form['popup']['close_button_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button image'),
      '#default_value' => $this->options['popup']['close_button_source'],
    ];
    $form['popup']['open_event'] = [
      '#type' => 'select',
      '#options' => [
        'click' => $this->t('On click'),
        'mouseover' => $this->t('On hover'),
      ],
      '#title' => $this->t('Mouse event for opening popup'),
      '#default_value' => $this->options['popup']['open_event'],
    ];
    $form['popup']['styled_google_map_view_shadow_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Shadow style'),
      '#options' => [0, 1, 2],
      '#description' => $this->t('1: shadow behind, 2: shadow below, 0: no shadow'),
      '#default_value' => $this->options['popup']['styled_google_map_view_shadow_style'],
    ];
    $form['popup']['styled_google_map_view_padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#field_suffix' => 'px',
      '#default_value' => $this->options['popup']['styled_google_map_view_padding'],
    ];
    $form['popup']['styled_google_map_view_border_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#field_suffix' => 'px',
      '#default_value' => $this->options['popup']['styled_google_map_view_border_radius'],
    ];
    $form['popup']['styled_google_map_view_border_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border width'),
      '#field_suffix' => 'px',
      '#default_value' => $this->options['popup']['styled_google_map_view_border_width'],
    ];
    $form['popup']['styled_google_map_view_border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#field_suffix' => '#hex',
      '#default_value' => $this->options['popup']['styled_google_map_view_border_color'],
    ];
    $form['popup']['styled_google_map_view_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#field_suffix' => '#hex',
      '#default_value' => $this->options['popup']['styled_google_map_view_background_color'],
    ];
    $form['popup']['styled_google_map_view_min_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min width'),
      '#field_suffix' => 'px (or auto)',
      '#default_value' => $this->options['popup']['styled_google_map_view_min_width'],
    ];
    $form['popup']['styled_google_map_view_max_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max width'),
      '#field_suffix' => 'px (or auto)',
      '#default_value' => $this->options['popup']['styled_google_map_view_max_width'],
    ];
    $form['popup']['styled_google_map_view_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min height'),
      '#field_suffix' => 'px (or auto)',
      '#default_value' => $this->options['popup']['styled_google_map_view_min_height'],
    ];
    $form['popup']['styled_google_map_view_max_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max height'),
      '#field_suffix' => 'px (or auto)',
      '#default_value' => $this->options['popup']['styled_google_map_view_max_height'],
    ];
    $form['popup']['styled_google_map_view_arrow_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Arrow style'),
      '#options' => [0, 1, 2],
      '#description' => $this->t('1: left side visible, 2: right side visible, 0: both sides visible'),
      '#default_value' => $this->options['popup']['styled_google_map_view_arrow_style'],
    ];
    $form['popup']['styled_google_map_view_arrow_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow size'),
      '#field_suffix' => 'px',
      '#default_value' => $this->options['popup']['styled_google_map_view_arrow_size'],
    ];
    $form['popup']['styled_google_map_view_arrow_position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow position'),
      '#field_suffix' => 'px',
      '#default_value' => $this->options['popup']['styled_google_map_view_arrow_position'],
    ];
    $form['popup']['styled_google_map_view_disable_auto_pan'] = [
      '#type' => 'select',
      '#title' => $this->t('Auto pan'),
      '#options' => [TRUE => $this->t('Yes'), FALSE => $this->t('No')],
      '#description' => $this->t('Automatically center the pin on click'),
      '#default_value' => $this->options['popup']['styled_google_map_view_disable_auto_pan'],
    ];
    $form['popup']['styled_google_map_view_hide_close_button'] = [
      '#type' => 'select',
      '#title' => $this->t('Hide close button'),
      '#options' => [TRUE => $this->t('Yes'), FALSE => $this->t('No')],
      '#description' => $this->t('Hide the popup close button'),
      '#default_value' => $this->options['popup']['styled_google_map_view_hide_close_button'],
    ];
    $form['popup']['styled_google_map_view_disable_animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Disable animation'),
      '#options' => [TRUE => $this->t('Yes'), FALSE => $this->t('No')],
      '#description' => $this->t('Disables the popup animation'),
      '#default_value' => $this->options['popup']['styled_google_map_view_disable_animation'],
    ];
    $form['popup_classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Popup classes'),
      '#description' => $this->t('CSS classes for easy popup styling'),
    ];
    $form['popup_classes']['styled_google_map_view_content_container_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper class'),
      '#default_value' => $this->options['popup_classes']['styled_google_map_view_content_container_class'],
    ];
    $form['popup_classes']['styled_google_map_view_background_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background class'),
      '#default_value' => $this->options['popup_classes']['styled_google_map_view_background_class'],
    ];
    $form['popup_classes']['styled_google_map_view_arrow_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow class'),
      '#default_value' => $this->options['popup_classes']['styled_google_map_view_arrow_class'],
    ];
    $form['popup_classes']['styled_google_map_view_arrow_outer_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow outer class'),
      '#default_value' => $this->options['popup_classes']['styled_google_map_view_arrow_outer_class'],
    ];
    $form['popup_classes']['styled_google_map_view_arrow_inner_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow inner class'),
      '#default_value' => $this->options['popup_classes']['styled_google_map_view_arrow_inner_class'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($results = []) {
    // We check if the views result are empty, or if the settings of this area
    // force showing this area even if the view is empty.
    if (!empty($this->view->live_preview)) {
      $output['preview'] = [
        '#markup' => '<p>' . $this->t('This is a preview of styled google map plugin. No map is displayed.') . '</p>',
      ];
      $output['maps'] = [
        '#markup' => '<p>' . $this->t('This map has @num points', ['@num' => count($results)]) . '</p>',
      ];
      return $output;
    }

    $locations = [];
    $heat_map_coords = [];
    if (!empty($results)) {
      // Get all geofield locations.
      foreach ($results as $row_index => $row) {
        $sourceHandlerOutput = [];
        // Render all fields first, so they are available for token replacement.
        /** @var \Drupal\views\Plugin\views\field\Field $handler */
        foreach ($this->displayHandler->getHandlers('field') as $field => $handler) {
          $handler->view->row_index = $row_index;
          $output = $handler->advancedRender($row);
          $placeholders = $handler->postRender($row, $output);
          $output = ViewsRenderPipelineMarkup::create(str_replace(array_keys($placeholders), array_values($placeholders), $output));
          $sourceHandlerOutput[$field] = $output;
        }
        $location = [];
        if (!empty($this->options['data_source']) && !empty($sourceHandlerOutput[$this->options['data_source']])) {
          // Add geofield data.
          try {
            $geom = geoPHP::load($sourceHandlerOutput[$this->options['data_source']]);
            if (!empty($geom)) {
              /** @var \Geometry $centroid */
              $centroid = $geom->getCentroid();
              $point = [];
              $point['lon'] = $centroid->getX();
              $point['lat'] = $centroid->getY();
              $location = $location + $point;
            }
            else {
              continue;
            }
          }
          catch (\exception $e) {
            continue;
          }

          if (!empty($this->options['default_pin_source'])) {
            $location['pin'] = file_create_url($this->options['default_pin_source']);
          }
          // Add pin image url.
          if (!empty($this->options['pin_source']) && !empty($sourceHandlerOutput[$this->options['pin_source']])) {
            /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface $sourceHandler */
            $sourceHandler = $this->displayHandler->getHandler('field', $this->options['pin_source']);
            $fileTargetId = $sourceHandler->render($row);
            if ($fileTargetId instanceof ViewsRenderPipelineMarkup) {
              $image = File::load($fileTargetId->__toString());
              $location = $location + [
                'pin' => file_create_url($image->getFileUri()),
              ];
              // Add the active pin image.
              if (!$this->options['main']['styled_google_map_view_active_pin']) {
                $location = $location + [
                  'active_pin' => file_create_url($image->getFileUri()),
                ];
              }
              else {
                $location = $location + [
                  'active_pin' => file_create_url($this->options['main']['styled_google_map_view_active_pin']),
                ];
              }
            }
            elseif (!empty($this->options['default_pin_source'])) {
              $location['pin'] = file_create_url($this->options['default_pin_source']);
            }
          }
          // Add marker Label.
          if (!empty($this->options['marker_label']) && isset($sourceHandlerOutput[$this->options['marker_label']])) {
            $markerLabelRenderArray = $sourceHandlerOutput[$this->options['marker_label']];
            $marker = render($markerLabelRenderArray);
            $marker = strip_tags($marker);
            $location = $location + [
              'marker_label' => $marker,
            ];
          }
          // Add pin popup html.
          if (!empty($this->options['popup_source']) && !empty($sourceHandlerOutput[$this->options['popup_source']])) {
            $popupRenderArray = $sourceHandlerOutput[$this->options['popup_source']];
            $location = $location + [
              'popup' => render($popupRenderArray),
            ];
          }
          // Add category.
          if (!empty($this->options['category_source']) && !empty($sourceHandlerOutput[$this->options['category_source']])) {
            $category = render($sourceHandlerOutput[$this->options['category_source']]);
            $location = $location + [
              'category' => Html::cleanCssIdentifier($category),
            ];
          }
        }
        if ($location) {
          $locations[] = $location;
        }
        // Gather heatmap coordinates.
        if (!empty($this->options['heatmap_settings']['data_source']) && !empty($sourceHandlerOutput[$this->options['heatmap_settings']['data_source']])) {
          // Add geofield data.
          try {
            $geom = geoPHP::load($sourceHandlerOutput[$this->options['heatmap_settings']['data_source']]);
            if (!empty($geom)) {
              /** @var \Geometry $centroid */
              $centroid = $geom->getCentroid();
              $point = [];
              $point['lon'] = $centroid->getX();
              $point['lat'] = $centroid->getY();
              $heat_map_coords[] = $point;
            }
          }
          catch (\exception $e) {
            // Do nothing just don't fall out.
          }
        }
      }
    }
    // Add custom settings.
    $cluster = [];
    $active_pin_image = '';
    if ($this->options['main']['styled_google_map_view_active_pin']) {
      $active_pin_image = $this->options['main']['styled_google_map_view_active_pin'];
    }
    if ($this->options['cluster_settings']['cluster_enabled']) {
      $cluster_pin_image = file_create_url($this->options['cluster_settings']['pin_image']);
      $cluster = ['pin_image' => $cluster_pin_image] + $this->options['cluster_settings'];
    }

    // Spider settings.
    $spider = [];
    if ($this->options['spider_settings']['spider_enabled']) {
      $spider_pin_image = file_create_url($this->options['spider_settings']['pin_image']);
      $spider = ['pin_image' => $spider_pin_image] + $this->options['spider_settings'];
    }
    // Heat map settings.
    $heat_map = [];
    if ($this->options['heatmap_settings']['heatmap_enabled'] && !empty($heat_map_coords)) {
      $heat_map = ['data' => $heat_map_coords] + $this->options['heatmap_settings'];
      if (!empty($heat_map['gradient'])) {
        $heat_map['gradient'] = explode("\r\n", $heat_map['gradient']);
      }
    }
    // @TODO: sanitize all options.
    $rand = rand();
    $map_id = $this->view->dom_id . $rand;
    $map_settings = [
      'id' => 'map_' . $map_id,
      'locations' => $locations,
      'settings' => [
        'gestureHandling' => $this->options['main']['styled_google_map_gesture_handling'],
        'height' => $this->options['main']['styled_google_map_view_height'],
        'width' => $this->options['main']['styled_google_map_view_width'],
        'maptypecontrol' => $this->options['main']['styled_google_map_view_maptypecontrol'],
        'scalecontrol' => $this->options['main']['styled_google_map_view_scalecontrol'],
        'rotatecontrol' => $this->options['main']['styled_google_map_view_rotatecontrol'],
        'draggable' => $this->options['main']['styled_google_map_view_draggable'],
        'mobile_draggable' => $this->options['main']['styled_google_map_view_mobile_draggable'],
        'streetviewcontrol' => $this->options['main']['styled_google_map_view_streetviewcontrol'],
        'style' => [
          'maptype' => $this->options['main']['styled_google_map_view_maptype'],
          'style' => $this->options['main']['styled_google_map_view_style'],
          'active_pin' => $active_pin_image,
        ],
        'zoom' => [
          'default' => $this->options['main']['styled_google_map_view_zoom_default'],
          'max' => $this->options['main']['styled_google_map_view_zoom_max'],
          'min' => $this->options['main']['styled_google_map_view_zoom_min'],
        ],
        'zoomcontrol' => $this->options['main']['styled_google_map_view_zoomcontrol'],
        'popup' => [
          'open_event' => $this->options['popup']['open_event'] ? $this->options['popup']['open_event'] : 'click',
          'disable_animation' => $this->options['popup']['styled_google_map_view_disable_animation'] ? TRUE : FALSE,
          'disable_autopan' => $this->options['popup']['styled_google_map_view_disable_auto_pan'] ? TRUE : FALSE,
          'hide_close_button' => $this->options['popup']['styled_google_map_view_hide_close_button'] ? TRUE : FALSE,
          'shadow_style' => $this->options['popup']['styled_google_map_view_shadow_style'],
          'padding' => $this->options['popup']['styled_google_map_view_padding'],
          'close_button_source' => !empty($this->options['popup']['close_button_source']) ? file_create_url($this->options['popup']['close_button_source']) : FALSE,
          'border_radius' => $this->options['popup']['styled_google_map_view_border_radius'],
          'border_width' => $this->options['popup']['styled_google_map_view_border_width'],
          'border_color' => $this->options['popup']['styled_google_map_view_border_color'],
          'background_color' => $this->options['popup']['styled_google_map_view_background_color'],
          'min_width' => $this->options['popup']['styled_google_map_view_min_width'],
          'max_width' => $this->options['popup']['styled_google_map_view_max_width'],
          'min_height' => $this->options['popup']['styled_google_map_view_min_height'],
          'max_height' => $this->options['popup']['styled_google_map_view_max_height'],
          'arrow_style' => $this->options['popup']['styled_google_map_view_arrow_style'],
          'arrow_size' => $this->options['popup']['styled_google_map_view_arrow_size'],
          'arrow_position' => $this->options['popup']['styled_google_map_view_arrow_position'],
          'classes' => [
            'container' => $this->options['popup_classes']['styled_google_map_view_content_container_class'],
            'background' => $this->options['popup_classes']['styled_google_map_view_background_class'],
            'arrow' => $this->options['popup_classes']['styled_google_map_view_arrow_class'],
            'arrow_outer' => $this->options['popup_classes']['styled_google_map_view_arrow_outer_class'],
            'arrow_inner' => $this->options['popup_classes']['styled_google_map_view_arrow_inner_class'],
          ],
        ],
      ],
    ];
    // If cluster feature is enabled.
    if (!empty($cluster)) {
      $map_settings['settings']['cluster'] = $cluster;
    }
    // If spiderfier feature is enabled.
    if (!empty($spider)) {
      $map_settings['settings']['spider'] = $spider;
    }
    // If heat map feature is enabled.
    if (!empty($heat_map)) {
      $map_settings['settings']['heat_map'] = $heat_map;
    }

    // Check if the custom map center option is enabled.
    if (!empty($this->options['main']['styled_google_map_default_map_center'])) {
      $map_settings['settings']['map_center'] = [
        'center_coordinates' => [
          'lat' => $this->options['main']['styled_google_map_default_map_center']['lat'],
          'lon' => $this->options['main']['styled_google_map_default_map_center']['lon'],
        ],
      ];
    }

    // Allow other modules to change the styled_google_map settings.
    $alter_vars = [
      'map_settings' => $map_settings,
      'context' => [
        'view' => $this->view,
        'options' => $this->options,
      ],
    ];
    \Drupal::moduleHandler()->alter('styled_google_map_views_style', $alter_vars);
    $map_settings = $alter_vars['map_settings'];

    // Prepare the output of the view style.
    $output = [];
    $output['#attached']['drupalSettings']['styled_google_map'] = ['map_' . $map_id => 'map_' . $map_id];
    $output['#attached']['drupalSettings']['maps'] = ['idmap_' . $map_id => $map_settings];
    // Output a div placeholder for the Styled Google Map.
    $output['styled_google_map']['#markup'] = '<div class="styled_map" id="map_' . $map_id . '"></div>';
    // Attach the Styled Google Map javascript file.
    $output['#attached']['library'][] = 'styled_google_map/styled-google-map';
    if (!empty($cluster)) {
      $output['#attached']['library'][] = 'styled_google_map/google-map-clusters';
    }
    if (!empty($spider)) {
      $output['#attached']['library'][] = 'styled_google_map/spiderfier';
    }
    if (!empty($heat_map)) {
      $output['#attached']['library'][] = 'styled_google_map/heatmap';
    }
    return $output;
  }

  /**
   * Always render the map even when there are no markers available.
   *
   * @return bool
   *   Returns whether the view should be rendered with no results.
   */
  public function evenEmpty() {
    return TRUE;
  }

}
