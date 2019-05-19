<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\ResourceInterface;

//use Drupal\visualn\Core\VisualNAdapterBase;

/**
 * Provides a 'RemoteXmlToJSArray' VisualN adapter.
 *
 * @ingroup adapter_plugins
 *
 * @VisualNAdapter(
 *  id = "visualn_xml",
 *  label = @Translation("Remote XML To JS Array Adapter"),
 *  input = "remote_xml_basic",
 * )
 */
class RemoteXmlToJSArrayAdapter extends RemoteDsvToJSArrayAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // @todo: no need to include dsv js library here
    //   see RemoteDsvToJSArrayAdapter::prepareBuild()
    $build['#attached']['library'][] = 'visualn/adapter-remote-xml-to-js-array';

    // This setting is required by the DSV/XML Adapter method
    // @todo: though it should be set in source provder
    $resource->file_mimetype = 'text/xml';

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
    return 'visualnRemoteXmlToJSArrayAdapter';
  }

}
