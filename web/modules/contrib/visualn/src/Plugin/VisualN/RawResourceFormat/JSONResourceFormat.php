<?php

/**
 * @file
 * Conatins JSONResourceFormat
 */

namespace Drupal\visualn\Plugin\VisualN\RawResourceFormat;

use Drupal\visualn\Core\RawResourceFormatBase;

/**
 * Provides a 'JSON' VisualN raw resource format.
 *
 * @ingroup raw_resource_formats
 *
 * @VisualNRawResourceFormat(
 *  id = "visualn_json",
 *  label = @Translation("JSON"),
 *  output = "remote_generic_json",
 * )
 */
class JSONResourceFormat extends RawResourceFormatBase {
}
