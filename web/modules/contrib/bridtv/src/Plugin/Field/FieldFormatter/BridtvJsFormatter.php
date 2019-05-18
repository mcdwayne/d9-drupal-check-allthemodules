<?php

namespace Drupal\bridtv\Plugin\Field\FieldFormatter;

/**
 * Formatter plugin for viewing Bridtv videos via JavaScript.
 *
 * @FieldFormatter(
 *   id = "bridtv_js",
 *   module = "bridtv",
 *   label = @Translation("JavaScript video player"),
 *   field_types = {
 *     "bridtv",
 *     "bridtv_reference"
 *   }
 * )
 */
class BridtvJsFormatter extends BridFormatterBase {}
