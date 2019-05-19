<?php

namespace Drupal\sample_data\Plugin\YamlContent;

use Drupal\yaml_content\ImportProcessorBase;

/**
 * Import processor to support entity queries and references.
 *
 * @ImportProcessor(
 *   id = "sample_data",
 *   label = @Translation("Sample Data Processor"),
 * )
 */
class SampleDataProcessor extends ImportProcessorBase {

  /**
   * A data loader instance to fetch sample data from.
   *
   * @var \Drupal\sample_data\SampleDataLoader
   */
  protected $dataLoader;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dataLoader = \Drupal::service('sample_data.loader');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {
    $config = $this->configuration;

    if (isset($config['dataset'])) {
      $data = $this->loadSampleDataSet($config['dataset']);

      $value = $data->get($config['lookup']);
    }
    elseif (isset($config['data_type'])) {
      $value = $this->dataLoader->loadSample($config['data_type'], $config);
    }

    if ($value) {
      $import_data[] = $value;
    }
  }

  /**
   * Load sample data set.
   *
   * The following keys are searched for within the $config array:
   * - module
   *   The module containing the data file to be loaded.
   * - path
   *   The path within the module to look for the data file. Defaults to
   *   `content/data`.
   * - file
   *   The name of the data file such that the file name is `<file>.data.yml`.
   *
   * @param array $config
   *   Configuration for the sample data set to be loaded.
   *
   * @return \Drupal\sample_data\SampleDataSet
   *   A sample data set to fetch sample data from.
   */
  protected function loadSampleDataSet(array $config) {
    $path = drupal_get_path('module', $config['module']);
    $path .= '/' . (isset($config['path']) ? $config['path'] : 'content/data');
    $path .= '/' . $config['file'] . '.data.yml';

    $data = $this->dataLoader->loadDataSet($path);

    return $data;
  }

}
