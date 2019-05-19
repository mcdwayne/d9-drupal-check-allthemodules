<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Core\DataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;

/**
 * Provides an 'Table Html Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_table_html_basic",
 *  label = @Translation("Table Html Basic"),
 *  compatible_drawers = {
 *    "visualn_table_html_basic"
 *  }
 * )
 */
class TableHtmlBasicDataGenerator extends DataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number_rows' => '5',
      'number_columns' => '3',
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['number_rows'] = [
      '#type' => 'number',
      '#title' => t('Number of rows'),
      '#default_value' => $this->configuration['number_rows'],
      '#min' => 1,
      '#max' => 15,
      '#required' => TRUE,
    ];
    $form['number_columns'] = [
      '#type' => 'number',
      '#title' => t('Number of columns'),
      '#default_value' => $this->configuration['number_columns'],
      '#min' => 1,
      '#max' => 5,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateData() {
    $data = [];

    $random = new Random();
    for ($i = 0; $i < $this->configuration['number_rows']; $i++) {
      $data_row = [];
      for ($j = 0; $j < $this->configuration['number_columns']; $j++) {
        $key = 'column' . ($j+1);
        $data_row[$key] = $random->word(mt_rand(2, 9));
      }
      $data[] = $data_row;
    }

    return $data;
  }

}
