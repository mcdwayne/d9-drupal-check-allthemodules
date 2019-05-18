<?php

namespace Drupal\phone_number\Plugin\Field\FieldFormatter;

use libphonenumber\PhoneNumberFormat;

/**
 * Plugin implementation of the 'phone_number_local' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_number_local",
 *   label = @Translation("Local Number"),
 *   field_types = {
 *     "phone_number",
 *     "telephone"
 *   }
 * )
 */
class PhoneNumberLocalFormatter extends PhoneNumberInternationalFormatter {

  public $phoneDisplayFormat = PhoneNumberFormat::NATIONAL;

}
