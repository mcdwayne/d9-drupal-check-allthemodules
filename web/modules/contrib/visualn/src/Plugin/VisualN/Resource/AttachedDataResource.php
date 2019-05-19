<?php

namespace Drupal\visualn\Plugin\VisualN\Resource;

use Drupal\visualn\Core\VisualNResourceBase;

// @todo: change "output" annotation value - resource returns just an array of data, the adapter itself attaches it
//    as drupalSettings and sends to the client side as JSON data
//    see default output type for the plugin annotation

/**
 * Provides an 'Attached Data Resource' VisualN resource.
 *
 * @VisualNResource(
 *  id = "generic_data_array",
 *  label = @Translation("Attached Data Resource"),
 *  output = "generic_data_array",
 * )
 */
class AttachedDataResource extends VisualNResourceBase {
  // @todo: output key seems to be not needed here in annotation
}
