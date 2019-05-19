<?php

namespace Drupal\simple_seo_preview\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The simple_seo_preview data type.
 *
 * The plain value of a simple_seo_preview is a serialized object represented
 * as a string.
 *
 * @DataType(
 *  id = "simple_seo_preview",
 *  label = @Translation("Simple SEO Preview")
 * )
 */
class SimpleSeoPreview extends StringData implements SimpleSeoPreviewInterface {

}
