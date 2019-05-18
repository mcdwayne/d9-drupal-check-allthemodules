<?php

namespace Drupal\bridtv\Plugin\Field\FieldFormatter;

/**
 * Formatter plugin for viewing Bridtv videos via Facebook Instant Articles (FIA).
 *
 * @FieldFormatter(
 *   id = "bridtv_fia",
 *   module = "bridtv",
 *   label = @Translation("Facebook Instant Articles (FIA)"),
 *   field_types = {
 *     "bridtv",
 *     "bridtv_reference"
 *   }
 * )
 */
class BridtvFIAFormatter extends BridFormatterBase {

  static protected $theme = 'bridtv_fia';

}
