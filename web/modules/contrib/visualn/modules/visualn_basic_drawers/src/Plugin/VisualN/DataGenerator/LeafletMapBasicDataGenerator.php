<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Core\DataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Leaflet Map Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_leaflet_map_basic",
 *  label = @Translation("Leaflet Map Basic"),
 *  compatible_drawers = {
 *    "visualn_leaflet_map_basic"
 *  }
 * )
 */
class LeafletMapBasicDataGenerator extends DataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'center_lat' => '51.8',
      'center_lon' => '104.8',
      'number' => '3',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<div>' . t('Enter coordinates for the center') . '</div>',
    ];
    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Center latitude'),
      '#default_value' => $this->configuration['center_lat'],
      '#required' => TRUE,
      '#size' => 10,
    ];
    $form['center_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Center longitude'),
      '#default_value' => $this->configuration['center_lon'],
      '#required' => TRUE,
      '#size' => 10,
    ];
    $form['number'] = [
      '#type' => 'number',
      '#title' => t('Number of points'),
      '#default_value' => $this->configuration['number'],
      '#min' => 0,
      '#max' => 15,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateData() {
    $data = [];

    // generate points latitude, longitude and title
    for ($i = 1; $i <= $this->configuration['number']; $i++) {
      $data[] = [
        'title' => $title = t('Point #') . $i,
        'lat' => $this->configuration['center_lat'] + mt_rand() / mt_getrandmax()*0.2 - 0.1,
        'lon' => $this->configuration['center_lon'] + mt_rand() / mt_getrandmax()*0.2 - 0.1
      ];
    }

    return $data;
  }

}
