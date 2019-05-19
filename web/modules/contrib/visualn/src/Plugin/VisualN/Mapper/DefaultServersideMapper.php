<?php

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Core\MapperBase;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Default Serverside' VisualN mapper.
 *
 * @ingroup mapper_plugins
 *
 * @VisualNMapper(
 *  id = "visualn_default_serverside",
 *  label = @Translation("Default Serverside Mapper"),
 *  input =  "generic_data_array",
 *  output =  "generic_data_array",
 * )
 */
class DefaultServersideMapper extends MapperBase {

  /**
   * {@inheritdoc}
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {

    // get data keys for remapping
    $dataKeysMap = $this->configuration['drawer_fields'];

    // @todo: values should be already trimmed here
    if (empty(array_filter($dataKeysMap))) {
      return $resource;
    }

    // @todo: also no need to remap if new key is the same as older key

    $data = $resource->data;
    foreach ($data as $k => $row) {
      $new_row = [];
      foreach ($dataKeysMap as $data_key_new => $data_key) {
        // @todo: values should be already trimmed here
        if (!empty($data_key)) {
          // skip empty mappings
          $new_row[$data_key_new] = $row[$data_key];
        }
        else {
          // just use older value, no need to remap
          $new_row[$data_key_new] = $row[$data_key_new];
        }
      }
      $data[$k] = $new_row;
    }

    $resource->data = $data;

    return $resource;
  }

}
