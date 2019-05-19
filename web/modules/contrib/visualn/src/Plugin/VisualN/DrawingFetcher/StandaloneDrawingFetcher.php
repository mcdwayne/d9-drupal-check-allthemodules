<?php

namespace Drupal\visualn\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\GenericDrawingFetcherBase;

/**
 * Provides a 'No data (standalone)' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_standalone",
 *  label = @Translation("No data (standalone)")
 * )
 */
class StandaloneDrawingFetcher extends GenericDrawingFetcherBase {

  const RAW_RESOURCE_FORMAT = 'visualn_generic_data_array';

  // @todo: add an option to show only styles using standalone drawers

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }

    // @todo: unsupported operand types error
    //    add default value into defaultConfiguration()
    // todo: should be array by default already, the check shouldn't be requried
    $drawer_config = $this->configuration['drawer_config'] ?: [];
    $drawer_fields = $this->configuration['drawer_fields'];

    $raw_resource_plugin_id = static::RAW_RESOURCE_FORMAT;
    $raw_input = [
      'data' => [],
    ];
    // @todo: add service in ::create() method
    $resource =
      \Drupal::service('plugin.manager.visualn.raw_resource_format')
      ->createInstance($raw_resource_plugin_id, [])
      ->buildResource($raw_input);

    // Get drawing window parameters
    $window_parameters = $this->getWindowParameters();

    // Get drawing build
    $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, '', $window_parameters);

    $drawing_markup = $build;


    return $drawing_markup;
  }
}

