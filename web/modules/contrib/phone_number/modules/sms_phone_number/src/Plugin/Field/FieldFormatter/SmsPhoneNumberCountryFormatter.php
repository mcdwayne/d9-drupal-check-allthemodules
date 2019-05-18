<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldFormatter;

use Drupal\phone_number\Plugin\Field\FieldFormatter\PhoneNumberCountryFormatter;

/**
 * Plugin implementation of the 'sms_phone_number_country' formatter.
 *
 * @FieldFormatter(
 *   id = "sms_phone_number_country",
 *   label = @Translation("Country"),
 *   field_types = {
 *     "sms_phone_number"
 *   }
 * )
 */
class SmsPhoneNumberCountryFormatter extends PhoneNumberCountryFormatter {

}
