<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerWithJsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Leaflet Map Basic' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_leaflet_map_basic",
 *  label = @Translation("Leaflet Map Basic"),
 * )
 */
class LeafletMapBasicDrawer extends DrawerWithJsBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Openstreetmap based leaflet map with geotags support');
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'center_lat' => -27.11667,
      'center_lon' => -109.35000,
      'map_height' => '',
      'calculate_center' => 1,
    ];
    return $default_config;
  }

  // @todo: remove whitespaces on submit, and validate to allow only
  //   valid coordinates values

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // get calculate_center #name property for #states settings
    $element_path = isset($form['#parents']) ? $form['#parents'] : [];
    $element_path[] = 'calculate_center';
    $name = array_shift($element_path);
    if (!empty($element_path)) {
      $name .= '[' . implode('][', $element_path) . ']';
    }

    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Center latitude'),
      '#default_value' => $this->configuration['center_lat'],
      '#required' => TRUE,
      '#size' => 10,
      '#states' => [
        'disabled' => [
          ':input[name="' . $name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['center_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Center longitude'),
      '#default_value' => $this->configuration['center_lon'],
      '#required' => TRUE,
      '#size' => 10,
      '#states' => [
        'disabled' => [
          ':input[name="' . $name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['calculate_center'] = [
      '#type' => 'checkbox',
      '#title' => t('Calculate center'),
      '#default_value' => $this->configuration['calculate_center'],
      '#description' => t('Get center based on points data. If no points provided, center latitude and longitude are used.'),
    ];
    $form['map_height'] = [
      '#type' => 'number',
      '#title' => t('Map height (px)'),
      '#default_value' => $this->configuration['map_height'],
      '#attributes' => ['placeholder' => '350'],
      '#min' => 1,
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {

    // check drawing window parameters
    $window_parameters = $this->getWindowParameters();
    if (!empty($window_parameters['height'])) {
      $this->configuration['map_height'] = $window_parameters['height'];
    }

    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_basic_drawers/leaflet-map-basic-drawer';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnLeafletMapBasicDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'title',
      'lon',
      'lat',
    ];

    return $data_keys;
  }

}
