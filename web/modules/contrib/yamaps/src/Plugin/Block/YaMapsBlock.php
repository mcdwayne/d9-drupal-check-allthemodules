<?php

namespace Drupal\yamaps\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a yandex map block.
 *
 * @Block(
 *   id = "yamaps_block",
 *   admin_label = @Translation("Yandex Map block"),
 * )
 */
class YaMapsBlock extends BlockBase {

  public const YAMAPS_LEGAL_AGREEMENT_URL = '//legal.yandex.ru/maps_api/';
  public const YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH = '100%';
  public const YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT = '400px';
  public const YAMAPS_DEFAULT_BLOCK_MAP_WIDTH = '168px';
  public const YAMAPS_DEFAULT_BLOCK_MAP_HEIGHT = '200px';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $block_output = [];
    $coords = Json::decode($config['yamaps_block_coords']);
    if (empty($coords)) {
      return $block_output;
    }

    $id = Html::getUniqueId(implode('-', ['ymap', 'block', 'yamaps']));

    $display_type = 'map';
    $width = $config['yamaps_block_width'];
    $height = $config['yamaps_block_height'];
    $traffic = $config['yamaps_block_traffic'];
    $auto_zoom = $config['yamaps_block_auto_zoom'];
    $clusterer = $config['yamaps_block_clusterer'];
    $controls = $config['yamaps_block_controls'];
    $block_type = $config['yamaps_block_type'];
    $placemarks = Json::decode($config['yamaps_block_placemarks']);
    $lines = Json::decode($config['yamaps_block_lines']);
    $polygons = Json::decode($config['yamaps_block_polygons']);
    $routes = Json::decode($config['yamaps_block_routes']);

    $map = [
      'init' => [
        'center' => $coords['center'],
        'zoom' => $coords['zoom'],
        'type' => $block_type,
        'behaviors' => \array_values(\array_filter($config['yamaps_block_behaviors'])),
      ],
      'display_options' => [
        'display_type' => $display_type,
      ],
      'controls' => $controls,
      'traffic' => $traffic,
      'clusterer' => $clusterer,
      'auto_zoom' => $auto_zoom,
      'placemarks' => $placemarks,
      'lines' => $lines,
      'polygons' => $polygons,
      'routes' => $routes,
      'edit' => FALSE,
    ];

    // Return map container div.
    $block_output['map_container'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => $id,
        'style' => 'width:' . $width . '; height:' . $height . ';',
        'class' => ['yamaps-map-container'],
      ],
      '#value' => '',
    ];

    $block_output['#attached']['library'][] = 'yamaps/yandex-map-api';
    $block_output['#attached']['library'][] = 'yamaps/yamaps-placemark';
    $block_output['#attached']['library'][] = 'yamaps/yamaps-map';
    $block_output['#attached']['drupalSettings']['yamaps'] = [$id => $map];

    return $block_output;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    // Unique map id.
    $mapId = Html::getUniqueId(implode('-', [
      'ymap',
      'yamaps',
      'edit',
    ]));

    // Add elements from default field edit form.
    $settings = [
      '#value' => [
        'coords' => !empty($config['yamaps_block_coords']) ? $config['yamaps_block_coords'] : NULL,
        'type' => !empty($config['yamaps_block_type']) ? $config['yamaps_block_type'] : 'yandex#map',
        'placemarks' => !empty($config['yamaps_block_placemarks']) ? $config['yamaps_block_placemarks'] : NULL,
        'lines' => !empty($config['yamaps_block_lines']) ? $config['yamaps_block_lines'] : NULL,
        'polygons' => !empty($config['yamaps_block_polygons']) ? $config['yamaps_block_polygons'] : NULL,
        'routes' => !empty($config['yamaps_block_routes']) ? $config['yamaps_block_routes'] : NULL,
      ],
    ];

    // Map information.
    $coords = $settings['#value']['coords'];
    $coords_array = Json::decode($settings['#value']['coords']);
    $type = $settings['#value']['type'];
    $placemarks = $settings['#value']['placemarks'];
    $placemarks_array = Json::decode($placemarks);
    $lines = $settings['#value']['lines'];
    $lines_array = Json::decode($lines);
    $polygons = $settings['#value']['polygons'];
    $polygons_array = Json::decode($polygons);
    $routes = $settings['#value']['routes'];
    $routes_array = Json::decode($routes);

    $form['yamaps_block_controls'] = [
      '#title' => $this->t('Show controls'),
      '#type' => 'checkbox',
      '#default_value' => !empty($config['yamaps_block_controls']) ? $config['yamaps_block_controls'] : TRUE,
    ];

    $form['yamaps_block_traffic'] = [
      '#title' => $this->t('Show traffic'),
      '#type' => 'checkbox',
      '#default_value' => !empty($config['yamaps_block_traffic']) ? $config['yamaps_block_traffic'] : FALSE,
    ];

    $form['yamaps_block_clusterer'] = [
      '#title' => $this->t('Use clusterer'),
      '#type' => 'checkbox',
      '#default_value' => !empty($config['yamaps_block_clusterer']) ? $config['yamaps_block_clusterer'] : FALSE,
    ];

    $form['yamaps_block_auto_zoom'] = [
      '#title' => $this->t('Auto zoom'),
      '#type' => 'checkbox',
      '#default_value' => !empty($config['yamaps_block_auto_zoom']) ? $config['yamaps_block_auto_zoom'] : FALSE,
    ];

    $form['yamaps_block_behaviors'] = [
      '#title' => $this->t('Available mouse events'),
      '#type' => 'checkboxes',
      '#options' => $this->getBehaviorsList(),
      '#default_value' => !empty($config['yamaps_block_behaviors']) ? $config['yamaps_block_behaviors'] : [],
    ];

    $form['yamaps_block_width'] = [
      '#title' => $this->t('Map width'),
      '#field_suffix' => ' ' . $this->t('in pixels (px) or percentage (%) for dynamic map, in pixels (px) for static map.'),
      '#type' => 'textfield',
      '#default_value' => !empty($config['yamaps_block_width']) ? $config['yamaps_block_width'] : self::YAMAPS_DEFAULT_BLOCK_MAP_WIDTH,
      '#size' => 5,
      '#element_validate' => [[$this, 'yamapsFieldValidatePixelsPercentage']],
      '#required' => TRUE,
    ];

    $form['yamaps_block_height'] = [
      '#title' => $this->t('Map height'),
      '#field_suffix' => ' ' . $this->t('in pixels (px) or percentage (%) for dynamic map, in pixels (px) for static map.'),
      '#type' => 'textfield',
      '#default_value' => !empty($config['yamaps_block_height']) ? $config['yamaps_block_height'] : self::YAMAPS_DEFAULT_BLOCK_MAP_HEIGHT,
      '#size' => 5,
      '#element_validate' => [[$this, 'yamapsFieldValidatePixelsPercentage']],
      '#required' => TRUE,
    ];

    $container_width = self::YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH;
    $container_height = self::YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT;

    // Map container.
    $form['map'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#attributes' => [
        'id' => $mapId,
        'class' => ['yamaps-map-container'],
        'style' => 'width: ' . $container_width . '; height: ' . $container_height . ';',
      ],
    ];

    $form['yamaps_block_coords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coordinates'),
      '#default_value' => $coords,
      '#attributes' => [
        'class' => ['field-yamaps-coords-' . $mapId],
        'style' => 'width: 100%;',
      ],
      '#description' => $this->t('Search for an object on the map to fill this field.'),
      '#required' => TRUE,
    ];

    // Hidden elements to save map information.
    $form['yamaps_block_type'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Type'),
      '#default_value' => $type,
      '#attributes' => ['class' => ['field-yamaps-type-' . $mapId]],
    ];

    $form['yamaps_block_placemarks'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Placemarks'),
      '#default_value' => $placemarks,
      '#attributes' => ['class' => ['field-yamaps-placemarks-' . $mapId]],
    ];

    $form['yamaps_block_lines'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Lines'),
      '#default_value' => $lines,
      '#attributes' => ['class' => ['field-yamaps-lines-' . $mapId]],
    ];

    $form['yamaps_block_polygons'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Polygons'),
      '#default_value' => $polygons,
      '#attributes' => ['class' => ['field-yamaps-polygons-' . $mapId]],
    ];

    $form['yamaps_block_routes'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Routes'),
      '#default_value' => $routes,
      '#attributes' => ['class' => ['field-yamaps-routes-' . $mapId]],
    ];

    $yamapsTermsUrl = Url::fromUri(self::YAMAPS_LEGAL_AGREEMENT_URL);
    // Map description.
    $form['#description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => Link::fromTextAndUrl(
        $this->t('Terms of service «API Yandex.Maps»'),
        $yamapsTermsUrl,
        ['attributes' => ['target' => '_blank']]
      ),
      '#attributes' => [
        'class' => ['yamaps-terms'],
      ],
    ];

    // Map initialization parameters.
    $map = [
      'id' => $mapId,
      'init' => [
        'center' => $coords_array['center'] ?? NULL,
        'zoom' => $coords_array['zoom'] ?? NULL,
        'type' => $type,
        'behaviors' => ['scrollZoom', 'dblClickZoom', 'drag'],
      ],
      'display_options' => [
        'display_type' => 'map',
        'width' => self::YAMAPS_DEFAULT_ADMIN_UI_MAP_WIDTH,
        'height' => self::YAMAPS_DEFAULT_ADMIN_UI_MAP_HEIGHT,
      ],
      'edit' => TRUE,
      'controls' => 1,
      'traffic' => 0,
      'clusterer' => 0,
      'auto_zoom' => 0,
      'placemarks' => $placemarks_array,
      'lines' => $lines_array,
      'polygons' => $polygons_array,
      'routes' => $routes_array,
    ];

    $form['#attached']['library'][] = 'yamaps/yamaps-placemark';
    $form['#attached']['library'][] = 'yamaps/yamaps-map';
    $form['#attached']['drupalSettings']['yamaps'] = [$mapId => $map];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['yamaps_block_controls'] = $form_state->getValue('yamaps_block_controls');
    $this->configuration['yamaps_block_traffic'] = $form_state->getValue('yamaps_block_traffic');
    $this->configuration['yamaps_block_clusterer'] = $form_state->getValue('yamaps_block_clusterer');
    $this->configuration['yamaps_block_auto_zoom'] = $form_state->getValue('yamaps_block_auto_zoom');
    $this->configuration['yamaps_block_behaviors'] = $form_state->getValue('yamaps_block_behaviors');
    $this->configuration['yamaps_block_width'] = $form_state->getValue('yamaps_block_width');
    $this->configuration['yamaps_block_height'] = $form_state->getValue('yamaps_block_height');
    $this->configuration['yamaps_block_coords'] = $form_state->getValue('yamaps_block_coords');
    $this->configuration['yamaps_block_type'] = $form_state->getValue('yamaps_block_type');
    $this->configuration['yamaps_block_placemarks'] = $form_state->getValue('yamaps_block_placemarks');
    $this->configuration['yamaps_block_lines'] = $form_state->getValue('yamaps_block_lines');
    $this->configuration['yamaps_block_polygons'] = $form_state->getValue('yamaps_block_polygons');
    $this->configuration['yamaps_block_routes'] = $form_state->getValue('yamaps_block_routes');
  }

  /**
   * Returns behaviors list.
   *
   * @return array
   *   Types of behaviours.
   */
  private function getBehaviorsList() {
    return [
      'clickZoom' => $this->t('Click Zoom'),
      'scrollZoom' => $this->t('Scroll Zoom'),
      'dblClickZoom' => $this->t('Double click zoom'),
      'drag' => $this->t('Click and drag'),
      'multiTouch' => $this->t('Multi Touch support'),
      'ruler' => $this->t('Ruler'),
      'rightMouseButtonMagnifier' => $this->t('Right mouse button magnifier'),
    ];
  }

  /**
   * Validate pixels or percentage value.
   *
   * @param array $element
   *   Field form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Field form state.
   */
  public function yamapsFieldValidatePixelsPercentage(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!preg_match('/^[1-9]{1}[0-9]*(px|%)$/', $value)) {
      $form_state->setErrorByName(
        $element['#name'],
        $this->t('%name must be a positive integer value and has "%" or "px" at the end.',
          ['%name' => $element['#title']]));
    }
  }

}
