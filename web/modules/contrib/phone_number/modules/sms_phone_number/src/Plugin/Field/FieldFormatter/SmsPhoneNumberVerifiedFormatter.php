<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'sms_phone_number_verified' formatter.
 *
 * @FieldFormatter(
 *   id = "sms_phone_number_verified",
 *   label = @Translation("Verified status"),
 *   field_types = {
 *     "sms_phone_number"
 *   }
 * )
 */
class SmsPhoneNumberVerifiedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $element = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\sms_phone_number\Plugin\Field\FieldType\SmsPhoneNumberItem $item */
      if ($util->getPhoneNumber($item->getValue()['value'])) {
        $element[$delta] = [
          '#markup' => '<span class="verified-status' . (!empty($item->verified) ? ' verified' : '') . '">' . (!empty($item->verified) ? (string) $this->t('Verified') : (string) $this->t('Not verified')) . '</span>',
        ];
      }
    }

    return $element;
  }

}
