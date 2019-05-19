<?php

/**
 * @file
 * Conatins CSVResourceFormat
 */

namespace Drupal\visualn\Plugin\VisualN\RawResourceFormat;

use Drupal\visualn\Core\RawResourceFormatBase;

/**
 * Provides a 'CSV' VisualN raw resource format.
 *
 * @ingroup raw_resource_formats
 *
 * @VisualNRawResourceFormat(
 *  id = "visualn_csv",
 *  label = @Translation("CSV"),
 *  output = "remote_generic_csv",
 * )
 */
class CSVResourceFormat extends RawResourceFormatBase {
}
