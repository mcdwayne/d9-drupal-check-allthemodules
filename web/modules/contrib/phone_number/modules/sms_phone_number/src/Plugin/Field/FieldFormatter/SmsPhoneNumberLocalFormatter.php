<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldFormatter;

use Drupal\phone_number\Plugin\Field\FieldFormatter\PhoneNumberLocalFormatter;

/**
 * Plugin implementation of the 'sms_phone_number_local' formatter.
 *
 * @FieldFormatter(
 *   id = "sms_phone_number_local",
 *   label = @Translation("Local Number"),
 *   field_types = {
 *     "sms_phone_number"
 *   }
 * )
 */
class SmsPhoneNumberLocalFormatter extends PhoneNumberLocalFormatter {

}
