<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Core\AdapterWithJsBase;
use Drupal\visualn\ResourceInterface;

/**
 * Provides an 'Attached JSON Data Adapter' VisualN adapter.
 *
 * @ingroup adapter_plugins
 *
 * @VisualNAdapter(
 *  id = "visualn_attached_json",
 *  label = @Translation("Data Array To JS Array Adapter"),
 *  input = "generic_data_array",
 * )
 */
// @todo: maybe remove default output type from annotation to avoid confusion and make it more explicit
class DataArrayToJSArrayAdapter extends AdapterWithJsBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach the data. Drupal js settings are attached in json format, thus so is the data for the drawing.
    $data = $resource->data;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterData'] = $data;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-data-array-to-js-array';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDataArrayToJSArrayAdapter';
  }

}
