<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Core\AdapterWithJsBase;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'RemoteDsvToJSArray' VisualN adapter.
 *
 * @ingroup adapter_plugins
 *
 * @VisualNAdapter(
 *  id = "visualn_file_generic_default",
 *  label = @Translation("Remote DSV To JS Array Adapter"),
 *  input = "remote_generic_dsv",
 * )
 */
class RemoteDsvToJSArrayAdapter extends AdapterWithJsBase {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    $url = $resource->file_url;

    $file_type = '';
    if (isset($resource->file_mimetype) && !empty($resource->file_mimetype)) {
      $file_mimetype = $resource->file_mimetype;
      switch ($file_mimetype) {
        case 'text/tab-separated-values' :
          $file_type = 'tsv';
          break;
        case 'text/csv' :
          $file_type = 'csv';
          break;
        case 'text/xml' :
        case 'application/xml' :
          $file_type = 'xml';
          break;
        case 'application/json' :
          $file_type = 'json';
          break;
      }
    }

    // @todo: do nothing if file type undefined or set a warning in js console

    // adapter specific js settings
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['fileUrl'] = $url;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['fileType'] = $file_type;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-remote-dsv-to-js-array';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnRemoteDsvToJSArrayAdapter';
  }

}
