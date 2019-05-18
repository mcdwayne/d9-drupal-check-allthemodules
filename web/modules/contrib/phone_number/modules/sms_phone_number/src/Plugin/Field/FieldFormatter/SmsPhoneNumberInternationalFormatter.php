<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldFormatter;

use Drupal\phone_number\Plugin\Field\FieldFormatter\PhoneNumberInternationalFormatter;

/**
 * Plugin implementation of the 'sms_phone_number_international' formatter.
 *
 * @FieldFormatter(
 *   id = "sms_phone_number_international",
 *   label = @Translation("International Number"),
 *   field_types = {
 *     "sms_phone_number"
 *   }
 * )
 */
class SmsPhoneNumberInternationalFormatter extends PhoneNumberInternationalFormatter {

}
