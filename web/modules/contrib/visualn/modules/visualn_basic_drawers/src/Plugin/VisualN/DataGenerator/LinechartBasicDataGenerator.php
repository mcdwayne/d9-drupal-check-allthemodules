<?php

// @todo: sync generator config with drawer preview config settings (?)

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Core\DataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

// @todo: as a good practice, use the same machine name as for main compatible drawer
//   document in best practices

/**
 * Provides an 'Linechart Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_linechart_basic",
 *  label = @Translation("Linechart Basic"),
 *  compatible_drawers = {
 *    "visualn_linechart_basic"
 *  }
 * )
 */
class LinechartBasicDataGenerator extends DataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number' => '5',
      'series_number' => '1',
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['number'] = [
      '#type' => 'number',
      '#title' => t('Number of points'),
      '#default_value' => $this->configuration['number'],
      '#min' => 1,
      '#max' => 15,
      '#required' => TRUE,
    ];

    // @todo: currently no way to set different number of points for each series
    $form['series_number'] = [
      '#type' => 'number',
      '#title' => t('Number of series'),
      '#default_value' => $this->configuration['series_number'],
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateData() {
    $data = [];
    for ($i = 0; $i < $this->configuration['number']; $i++) {
      $data[$i]['x'] = $i+1;
      for ($j = 1; $j <= $this->configuration['series_number']; $j++) {
        $data[$i]['data' . $j] = mt_rand(0, 9);
      }
    }

    return $data;
  }

}
