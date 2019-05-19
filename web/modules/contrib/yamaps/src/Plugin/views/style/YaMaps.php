<?php

namespace Drupal\yamaps\Plugin\views\style;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\yamaps\Geocoding;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allow to display several field items on a yandex map.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "ya_maps",
 *   title = @Translation("Yandex map"),
 *   help = @Translation("Display yamaps items on a Yandex map."),
 *   theme = "views_view_list",
 *   display_types = {"normal"},
 * )
 */
class YaMaps extends StylePluginBase {

  public const YAMAPS_DEFAULT_FORMATTER = 'yamaps_default';
  public const YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH = '100%';
  public const YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT = '400px';

  public const PLACEMARK_TITLE = 'iconContent';
  public const PLACEMARK_BALLON_HEADER = 'balloonContentHeader';
  public const PLACEMARK_BALLON_BODY = 'balloonContentBody';
  public const PLACEMARK_DEFAULT_FIELD = '<default>';
  public const PLACEMARK_NONE_FIELD = '<none>';

  protected $usesFields = TRUE;
  protected $usesRowPlugin = TRUE;
  protected $usesRowClass = FALSE;
  protected $usesGrouping = FALSE;

  /**
   * YaMaps geocoding service.
   *
   * @var \Drupal\yamaps\Geocoding
   */
  protected $geocoding;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Geocoding $geocoding) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->geocoding = $geocoding;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('yamaps.geocoding')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return $this->options['even_empty'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $placemarks = [];
    $prepared_lines = [];
    $prepared_polygons = [];
    $prepared_route = [];
    $this->options['coords'] = $this->getCoordinates();
    $prepared_params = $this->geocoding->decodeParams($this->options);

    if (empty($this->options['yandex_map_field'])) {
      return $this->t('Please add "Yandex Maps" field and chose it in views settings form.');
    }

    $yandexmap_field_type = $this->view->field[$this->options['yandex_map_field']]->options['type'];

    if ($yandexmap_field_type === static::YAMAPS_DEFAULT_FORMATTER &&
      (!isset($prepared_params['coords']['center']) || (isset($prepared_params['coords']['center']) && !is_array($prepared_params['coords']['center'])))) {
      return $this->t('The Static Yandex Maps style cannot be used without coordinates of center.');
    }

    $yandexmap_field_name = $this->options['yandex_map_field'];
    $yandexmap_field_settings = $this->view->field[$this->options['yandex_map_field']]->options['settings'];

    foreach ($this->view->result as $row_index => $row) {
      // Fix yandex cart.
      $yandexmap_field = $this->view->field[$yandexmap_field_name];
      $yandexmap_field_entity = $yandexmap_field->getEntity($row);
      if (isset($yandexmap_field_entity->{$yandexmap_field->definition['field_name']})) {
        $yandexmap_field_value = $yandexmap_field_entity->{$yandexmap_field->definition['field_name']}->getValue();
        foreach ($yandexmap_field_value as $yandexmap_field_coords) {
          if (isset($yandexmap_field_coords['placemarks'])) {
            // Preparing placemarks.
            $decoded_placemarks = Json::decode($yandexmap_field_coords['placemarks']);

            if (is_array($decoded_placemarks)) {
              foreach ($decoded_placemarks as $placemark) {
                // Override placemark title.
                $this->overridePlacemarkTitle($placemark, $row);

                // Prepare Balloon title.
                if ($this->options['balloon_title'] && $this->options['balloon_title'] !== static::PLACEMARK_DEFAULT_FIELD) {
                  $balloon_title = '';
                  $balloon_title_field = $this->view->field[$this->options['balloon_title']];
                  if ($balloon_title_field != NULL) {
                    $balloon_title_field_entity = $balloon_title_field->getEntity($row);
                    $balloon_title_field_values = $balloon_title_field_entity->{$balloon_title_field->definition['field_name']}->getValue();
                    $balloon_title = (!empty($balloon_title_field_values[0]['value'])) ? $balloon_title_field_values[0]['value'] : '';
                  }
                  $placemark['params'][static::PLACEMARK_BALLON_HEADER] = $balloon_title;
                }
                // Prepare Balloon body.
                if (isset($this->options['balloon_body']) && is_array($this->options['balloon_body'])) {
                  $balloon_body = [];
                  foreach ($this->options['balloon_body'] as $bval) {
                    if (!empty($this->view->field[$bval])) {
                      $balloon_body_field = $this->view->field[$bval];
                      $balloon_body_field_entity = $balloon_body_field->getEntity($row);
                      $balloon_body_field_values = $balloon_body_field_entity->{$balloon_body_field->definition['field_name']}->getValue();
                      $balloon_body[] = (!empty($balloon_body_field_values[0]['value'])) ? $balloon_body_field_values[0]['value'] : '';
                    }
                  }
                  $placemark['params'][static::PLACEMARK_BALLON_BODY] = $this->prepareBody($balloon_body);
                }
                $this->view->row_index = $row_index;
                unset($balloon_body);
                $placemarks[] = $placemark;
              }
            }
          }
          // Preparing lines.
          if (isset($yandexmap_field_coords['lines'])) {
            $decoded_lines = Json::decode($yandexmap_field_coords['lines']);
            if (is_array($decoded_lines)) {
              foreach ($decoded_lines as $lines) {
                $prepared_lines[] = $lines;
              }
            }
          }
          // Preparing polygons.
          if (isset($yandexmap_field_coords['polygons'])) {
            $decoded_polygons = Json::decode($yandexmap_field_coords['polygons']);
            if (is_array($decoded_polygons)) {
              foreach ($decoded_polygons as $polygons) {
                $prepared_polygons[] = $polygons;
              }
            }
          }
          // Preparing routes.
          if (isset($yandexmap_field_coords['routes'])) {
            $decoded_routes = Json::decode($yandexmap_field_coords['routes']);
            if (is_array($decoded_routes)) {
              foreach ($decoded_routes as $route) {
                $prepared_route[] = $route;
              }
            }
          }
        }
      }
    }

    unset($this->view->row_index);

    $array_of_unique_params = [
      $this->getPluginId(),
      $this->view->getDisplay()->getType(),
      $this->view->current_display,
    ];

    if (isset($this->view->dom_id)) {
      $array_of_unique_params[] = $this->view->dom_id;
    }
    // Unique map id.
    $id = Html::getUniqueId(implode('-', $array_of_unique_params));

    switch ($this->options['yamaps_center_options']['map_center_type']) {
      case 'geolocation':
        $prepared_params['coords']['center'] = NULL;
        $parameters = $this->geocoding->geocode($this->options['yamaps_center_options']['map_center_geolocation']);

        if (isset($parameters) && $parameters !== FALSE) {
          $prepared_params['coords']['center'] = $parameters['map_center'];
        }
        $prepared_params['coords']['zoom'] = ++$this->options['yamaps_center_options']['zoom'];
        $prepared_params['type'] = 'yandex#map';
        break;

      case 'mini_map':
        // Merging placemark.
        if (is_array($prepared_params['placemarks'])) {
          foreach ($prepared_params['placemarks'] as $p) {
            $placemarks[] = $p;
          }
        }
        // Merging lines.
        if (is_array($prepared_params['lines'])) {
          foreach ($prepared_params['lines'] as $lines) {
            $prepared_lines[] = $lines;
          }
        }
        // Merging polygons.
        if (is_array($prepared_params['polygons'])) {
          foreach ($prepared_params['polygons'] as $polygon) {
            $prepared_polygons[] = $polygon;
          }
        }
        // Merging routes.
        if (is_array($prepared_params['routes'])) {
          foreach ($prepared_params['routes'] as $route) {
            $prepared_route[] = $route;
          }
        }
        break;
        $map_behaviors = [];
        if ($yandexmap_field_settings['enable_zoom']) {
          $map_behaviors[] = 'scrollZoom';
          $map_behaviors[] = 'dblClickZoom';
        }

        if ($yandexmap_field_settings['enable_drag']) {
          $map_behaviors[] = 'drag';
        }
    }

    $build = [];

    // Map initialization parameters.
    $map = [
      'id' => $id,
      'init' => [
        'center' => $prepared_params['coords']['center'] ?? NULL,
        'zoom' => $prepared_params['coords']['zoom'] ?? NULL,
        'type' => $prepared_params['type'],
        'behaviors' => $map_behaviors,
      ],
      'display_options' => [
        'display_type' => 'map',
        'width' =>  isset($yandexmap_field_settings['width']) ? $yandexmap_field_settings['width'] : self::YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH,
        'height' =>  isset($yandexmap_field_settings['height']) ? $yandexmap_field_settings['height'] : self::YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT,
      ],
      'edit' => FALSE,
      'controls' => 1,
      'placemarks' => empty($placemarks) ? NULL : $placemarks,
      'lines' => empty($prepared_lines) ? NULL : $prepared_lines,
      'polygons' => empty($prepared_polygons) ? NULL : $prepared_polygons,
      'routes' => empty($prepared_route) ? NULL : $prepared_route,
    ];

    $map_class = ['yamaps-map-container'];

    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'style' => ' width: ' . $yandexmap_field_settings['width'] . '; height:' . $yandexmap_field_settings['height'] . ';',
        'id' => $id,
        'class' => $map_class,
      ],
      '#value' => '',
    ];

    $build['#attached']['library'][] = 'yamaps/yandex-map-api';
    $build['#attached']['library'][] = 'yamaps/yamaps-placemark';
    $build['#attached']['library'][] = 'yamaps/yamaps-line';
    $build['#attached']['library'][] = 'yamaps/yamaps-polygon';
    $build['#attached']['library'][] = 'yamaps/yamaps-map';
    $build['#attached']['drupalSettings']['yamaps'] = [$id => $map];

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['yandex_map_field'] = ['default' => ''];
    $options['placemarks'] = ['default' => ''];
    $options['lines'] = ['default' => ''];
    $options['polygons'] = ['default' => ''];
    $options['routes'] = ['default' => ''];
    $options['yamaps_center_options'] = [
      'default' => [
        'map_center_type' => 'geolocation',
        'map_center_geolocation' => '',
        'zoom' => 6,
        'map_container' => ['coords' => ''],
      ],
    ];
    $options['placemark_title'] = ['default' => static::PLACEMARK_DEFAULT_FIELD];
    $options['balloon_title'] = ['default' => static::PLACEMARK_DEFAULT_FIELD];
    $options['balloon_body'] = ['default' => static::PLACEMARK_DEFAULT_FIELD];
    $options['type'] = ['default' => 'yandex#map'];
    $options['map_center'] = ['default' => ''];
    $options['map_grouping_cat'] = ['default' => 'standard'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $fields = $this->getFields();
    $yandex_fields = $this->getYandexMapsFields();

    $form['yandex_map_field'] = [
      '#title' => $this->t('Yandex Map Field'),
      '#description' => $this->t('Choose Yandex Maps field. Add if views fields this field for the first.'),
      '#type' => 'select',
      '#options' => $yandex_fields,
      '#required' => TRUE,
      '#default_value' => $this->options['yandex_map_field'],
    ];
    $form['yamaps_center_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map center'),
      '#states' => [
        'invisible' => [
          ':input[name="style_options[yandex_map_field]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['yamaps_center_options']['map_center_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose map center type'),
      '#options' => [
        'geolocation' => $this->t('Geolocation.'),
        'mini_map' => $this->t('Choose on map.'),
      ],
      '#default_value' => $this->options['yamaps_center_options']['map_center_type'],
      '#required' => FALSE,
      '#description' => $this->t('Type of map displaying.'),
    ];
    $form['yamaps_center_options']['map_center_geolocation'] = [
      '#title' => $this->t('Map center geolocation'),
      '#description' => $this->t('Please enter place on whitch map will be centered.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['yamaps_center_options']['map_center_geolocation'],
      '#size' => 40,
      '#states' => [
        'visible' => [
          ':input[name="style_options[yamaps_center_options][map_center_type]"]' => ['value' => 'geolocation'],
        ],
      ],
    ];
    $form['yamaps_center_options']['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'select',
      '#description' => $this->t('Zoom of map'),
      '#options' => range(1, 15),
      '#states' => [
        'visible' => [
          ':input[name="style_options[yamaps_center_options][map_center_type]"]' => ['value' => 'geolocation'],
        ],
      ],
      '#default_value' => $this->options['yamaps_center_options']['zoom'],
    ];

    $this->options['coords'] = $this->getCoordinates();
    $decoded_params = $this->geocoding->decodeParams($this->options);

    // Map initialization parameters.
    $map = [
      'init' => [
        'center' => $decoded_params['coords']['center'] ?? NULL,
        'zoom' => $decoded_params['coords']['zoom'] ?? NULL,
        'type' => 'yandex#map',
        'behaviors' => ['scrollZoom', 'dblClickZoom', 'drag'],
      ],
      'display_options' => [
        'display_type' => 'map',
        'width' => static::YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH,
        'height' => static::YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT,
      ],
      'controls' => 1,
      'placemarks' => $decoded_params['placemarks'],
      'edit' => FALSE,
    ];

    $id = Html::getUniqueId(implode('-', [
      $this->getPluginId(),
      $this->view->getDisplay()->getType(),
      $this->view->current_display,
      'style_options_form',
    ]));

    if ($this->options['yandex_map_field']) {
      $yandexmap_field_settings = $this->view->display_handler->handlers['field'][$this->options['yandex_map_field']]->options['settings'];
    }

    // Set width and height.
    if (isset($yandexmap_field_settings['width']) && isset($yandexmap_field_settings['height'])) {
      $width = $yandexmap_field_settings['width'];
      $height = $yandexmap_field_settings['height'];
    }
    else {
      $width = static::YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH;
      $height = static::YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT;
    }

    $form['yamaps_center_options']['map_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map center'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[yamaps_center_options][map_center_type]"]' => ['value' => 'mini_map'],
        ],
      ],
    ];
    // Map container.
    $form['yamaps_center_options']['map_container']['map'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#description' => $this->t('Map view will be used when "Choose on map." radio is active'),
      '#attributes' => [
        'style' => ' width: ' . $width . '; height:' . $height . ';',
        'id' => $id,
        'class' => [
          'yamaps-map-container',
        ],
      ],
    ];
    $form['yamaps_center_options']['map_container']['coords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coordinates'),
      '#default_value' => $this->getCoordinates(),
      '#attributes' => [
        'class' => ['field-yamaps-coords-' . $id],
        'style' => 'width: 100%;',
      ],
      '#states' => [
        'invisible' => [
          ':input[name="style_options[yandex_map_field]"]' => ['value' => ''],
        ],
        'visible' => [
          ':input[name="style_options[yamaps_center_options][map_center_type]"]' => ['value' => 'mini_map'],
        ],
      ],
      '#description' => $this->t('Search for an object on the map to fill this field or leave it blank (if field is not required).'),
    ];
    // Hidden elements to save map information.
    $form['type'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Type'),
      '#default_value' => $this->options['type'],
      '#attributes' => ['class' => ['field-yamaps-type-' . $id]],
    ];
    // Hidden elements to saving map information.
    $form['placemarks'] = [
      '#type' => 'hidden',
      '#default_value' => $this->options['placemarks'],
      '#attributes' => ['class' => ['field-yamaps-placemarks-' . $id]],
    ];
    $form['lines'] = [
      '#type' => 'hidden',
      '#default_value' => $this->options['lines'],
      '#attributes' => ['class' => ['field-yamaps-lines-' . $id]],
    ];
    $form['polygons'] = [
      '#type' => 'hidden',
      '#default_value' => $this->options['polygons'],
      '#attributes' => ['class' => ['field-yamaps-polygons-' . $id]],
    ];
    $form['routes'] = [
      '#type' => 'hidden',
      '#default_value' => $this->options['routes'],
      '#attributes' => ['class' => ['field-yamaps-routes-' . $id]],
    ];
    // Load library.
    $form['#attached']['library'][] = 'yamaps/yamaps-placemark';
    $form['#attached']['library'][] = 'yamaps/yamaps-map';
    $form['#attached']['drupalSettings']['yamaps'] = [$id => $map];

    $form['placemark_title'] = [
      '#title' => $this->t('Placemark title'),
      '#type' => 'select',
      '#options' => $fields,
      '#default_value' => $this->options['placemark_title'],
      '#states' => [
        'invisible' => [
          ':input[name="style_options[yandex_map_field]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['balloon_title'] = [
      '#title' => $this->t('Balloon title'),
      '#type' => 'select',
      '#options' => $fields,
      '#default_value' => $this->options['balloon_title'],
      '#states' => [
        'invisible' => [
          ':input[name="style_options[yandex_map_field]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['balloon_body'] = [
      '#title' => $this->t('Balloon body Field'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $fields,
      '#default_value' => $this->options['balloon_body'],
      '#states' => [
        'invisible' => [
          ':input[name="style_options[yandex_map_field]"]' => ['value' => ''],
        ],
      ],
    ];
  }

  /**
   * Returns field names.
   *
   * @return array
   *   Fields list.
   */
  public function getFields() {
    $field_names = [
      '' => $this->t('@PLACEMARK_NONE_FIELD', ['@PLACEMARK_NONE_FIELD' => static::PLACEMARK_NONE_FIELD]),
      static::PLACEMARK_DEFAULT_FIELD => $this->t('Default balloon value'),
    ];
    $fields = $this->displayHandler->getHandlers('field');
    foreach ($fields as $id => $handler) {
      if (isset($handler->human_name)) {
        $field_names[$id] = $handler->human_name;
      }
      else {
        $field_names[$id] = $handler->definition['title'];
      }
    }
    return $field_names;
  }

  /**
   * Returns yandex maps specific field names.
   *
   * @return array
   *   List of yandex maps fields.
   */
  public function getYandexMapsFields() {
    $field_names = [
      '' => $this->t('@PLACEMARK_NONE_FIELD', ['@PLACEMARK_NONE_FIELD' => static::PLACEMARK_NONE_FIELD]),
    ];
    $fields = $this->displayHandler->getHandlers('field');
    $field_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('yamaps');
    $yamap_fields = [];
    foreach ($field_map as $entity_type_fields) {
      foreach ($entity_type_fields as $field_id => $field_data) {
        $yamap_fields[$field_id] = $field_id;
      }
    }

    foreach ($fields as $id => $handler) {
      if (isset($yamap_fields[$id])) {
        $field_names[$id] = $handler->definition['title'];
      }
    }

    return $field_names;
  }

  /**
   * Returns map coordinates.
   *
   * Get coordinates with support of old coordinates place.
   *
   * @return string
   *   List options 'coords'.
   */
  public function getCoordinates() {
    if (isset($this->options['coords'])) {
      return $this->options['coords'];
    }
    elseif (isset($this->options['yamaps_center_options']['map_container']['coords'])) {
      return $this->options['yamaps_center_options']['map_container']['coords'];
    }
    else {
      return '';
    }
  }

  /**
   * Override Placemark title.
   *
   * @param array $placemark
   *   Placemark.
   * @param \Drupal\views\ResultRow $row
   *   Row.
   */
  private function overridePlacemarkTitle(array &$placemark, ResultRow $row) {
    if (isset($this->options['placemark_title'], $this->view->field[$this->options['placemark_title']]) && $this->options['placemark_title'] !== static::PLACEMARK_DEFAULT_FIELD) {
      // Prepare placemark title.
      $marker_title = $this->preparePlacemarkTitle($row);
      $field_title_settings = $this->view->field[$this->options['placemark_title']];

      if (isset($field_title_settings->field_info['type'])) {
        switch ($field_title_settings->field_info['type']) {
          case 'image':
            if (isset($row->{'field_' . $this->options['placemark_title']}[0]['rendered']['#image_style'], $row->{'field_' . $this->options['placemark_title']}[0]['raw']['uri'])) {
              // Special logic for image fields.
              // Placemark type.
              $placemark['options']['iconLayout'] = 'default#image';
              // Image href.
              $placemark['options']['iconImageHref'] = ImageStyle::load($row->{'field_' . $this->options['placemark_title']}[0]['rendered']['#image_style'])->buildUrl($row->{'field_' . $this->options['placemark_title']}[0]['raw']['uri']);
              $image_dimensions = getimagesize($placemark['options']['iconImageHref']);
              // Placemark image size.
              $placemark['options']['iconImageSize'] = [
                $image_dimensions[0],
                $image_dimensions[1],
              ];

              // Icon image offset of upper left angle.
              $placemark['options']['iconImageOffset'] = [
                -($image_dimensions[0] / 2),
                $image_dimensions[1] * 0.1 - $image_dimensions[1],
              ];
            }
            else {
              $this->prepareDefaultPlacemarkTitle($placemark, $marker_title);
            }
            break;

          default:
            $this->prepareDefaultPlacemarkTitle($placemark, $marker_title);
            break;
        }
      }
      else {
        $this->prepareDefaultPlacemarkTitle($placemark, $marker_title);
      }
    }
  }

  /**
   * Prepare default placemark.
   *
   * @param array $placemark
   *   Placemark.
   * @param string $marker_title
   *   Marker title.
   */
  private function prepareDefaultPlacemarkTitle(array &$placemark, $marker_title) {
    $placemark['params'][static::PLACEMARK_TITLE] = $marker_title;
  }

  /**
   * Prepare placemark for the map.
   *
   * @param \Drupal\views\ResultRow $row
   *   Row value.
   *
   * @return string
   *   Prepared string.
   */
  private function preparePlacemarkTitle(ResultRow $row) {
    $title = '';
    $placemark_title_field = $this->view->field[$this->options['placemark_title']];
    if ($placemark_title_field != NULL) {
      $placemark_title_field_entity = $placemark_title_field->getEntity($row);
      $placemark_title_field_values = $placemark_title_field_entity->{$placemark_title_field->definition['field_name']}->getValue();
      $title = (!empty($placemark_title_field_values[0]['value'])) ? $placemark_title_field_values[0]['value'] : '';
    }

    return Html::escape(strip_tags($title));
  }

  /**
   * Prepares map body.
   *
   * @param array $body_array
   *   Body array.
   *
   * @return string
   *   Prepared body.
   */
  public function prepareBody(array $body_array) {
    $output = '<div class="balloon-inner">';
    foreach ($body_array as $key => $val) {
      $output .= '<span class="' . $key . '">' . $val . '</span>';
    }
    $output .= '</div>';
    return $output;
  }

}
