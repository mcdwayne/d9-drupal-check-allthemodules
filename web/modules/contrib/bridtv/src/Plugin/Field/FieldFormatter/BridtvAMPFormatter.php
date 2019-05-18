<?php

namespace Drupal\bridtv\Plugin\Field\FieldFormatter;

/**
 * Formatter plugin for viewing Bridtv videos via Accelerated Mobile Pages (AMP).
 *
 * @FieldFormatter(
 *   id = "bridtv_amp",
 *   module = "bridtv",
 *   label = @Translation("Accelerated Mobile Pages (AMP)"),
 *   field_types = {
 *     "bridtv",
 *     "bridtv_reference"
 *   }
 * )
 */
class BridtvAMPFormatter extends BridFormatterBase {

  static protected $theme = 'bridtv_amp';

}
