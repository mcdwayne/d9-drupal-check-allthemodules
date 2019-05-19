<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\ResourceInterface;

//use Drupal\visualn\Core\VisualNAdapterBase;

/**
 * Provides a 'RemoteJsonToJSArray' VisualN adapter.
 *
 * @ingroup adapter_plugins
 *
 * @VisualNAdapter(
 *  id = "visualn_json",
 *  label = @Translation("Remote jSON To JS Array Adapter"),
 *  input = "remote_generic_json",
 * )
 */
class RemoteJsonToJSArrayAdapter extends RemoteDsvToJSArrayAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // This setting is required by the DSV/JSON Adapter method
    // @todo: though it should be set in source provder
    $resource->file_mimetype = 'application/json';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    return $resource;
  }

}
