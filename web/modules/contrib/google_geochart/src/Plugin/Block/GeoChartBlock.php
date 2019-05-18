<?php

namespace Drupal\google_geochart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use phpDocumentor\Reflection\Types\This;

/**
 * Provides a 'GeoChartBlock' block.
 *
 * @Block(
 *  id = "geo_chart_block",
 *  admin_label = @Translation("Geo chart block"),
 * )
 */
class GeoChartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [['Country', 'Popularity'], ['Germany', 200], ['United States', 300], ['Brazil', 400], ['Canada', 500], ['France', 600], ['RU', 700]] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    if (empty($this->configuration['google_mapsapikey'])) {
      $default_maps_key = \Drupal::service('google_geochart.visualization_data')->getMapsApiKey();
    }
    else {
      $default_maps_key = $this->configuration['google_mapsapikey'];
    }

    $form['google_mapsapikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google mapsApiKey'),
      '#description' => $this->t('See https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings'),
      '#default_value' => $default_maps_key,
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['google_visualization_array_to_da'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default data (Json encoded array)'),
      '#description' => $this->t('Google visualization array to DataTable, Json encoded array Example like ==> "[[\"Country\",\"Popularity\"],[\"Germany\",200],[\"United States\",300],[\"Brazil\",400],[\"Canada\",500],[\"France\",600],[\"RU\",700]]" '),
      '#default_value' => $this->configuration['google_visualization_array_to_da'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['google_mapsapikey'] = $form_state->getValue('google_mapsapikey');
    $this->configuration['google_visualization_array_to_da'] = $form_state->getValue('google_visualization_array_to_da');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = \Drupal::service('google_geochart.visualization_data')->getGeochartData();
    if (!is_array($data)) {
      $country = json_decode($data);
    }
    else {
      $country = $data;
    }
//    $test_data = [['Country', 'Popularity'], ['India', 200], ['United Kingdom', 300]];
//    $t = \Drupal::service('google_geochart.visualization_data')->setGeochartData($test_data);
    $build = [
      '#theme' => 'google_geochart',
      '#attached' => [
        'library' => [
          'google_geochart/google_geochart',
        ],
        'drupalSettings' => [
          'google' => [
            'geochart' => [
              'mapsApiKey' => 'AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY',
              'country' => $country,
            ]
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
