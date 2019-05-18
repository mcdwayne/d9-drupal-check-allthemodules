<?php

namespace Drupal\mobile_number\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'mobile_number_local' formatter.
 *
 * @FieldFormatter(
 *   id = "mobile_number_local",
 *   label = @Translation("Local Number"),
 *   field_types = {
 *     "mobile_number",
 *     "telephone"
 *   }
 * )
 */
class MobileNumberLocalFormatter extends MobileNumberInternationalFormatter {

  public $phoneDisplayFormat = 2;

}
