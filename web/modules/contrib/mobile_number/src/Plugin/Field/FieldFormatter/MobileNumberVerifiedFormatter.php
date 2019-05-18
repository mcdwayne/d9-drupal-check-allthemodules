<?php

namespace Drupal\mobile_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'mobile_number_verified' formatter.
 *
 * @FieldFormatter(
 *   id = "mobile_number_verified",
 *   label = @Translation("Verified status"),
 *   field_types = {
 *     "mobile_number"
 *   }
 * )
 */
class MobileNumberVerifiedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $element = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem $item */
      if ($mobile_number = $util->getMobileNumber($item->getValue()['value'], NULL, [])) {
        $element[$delta] = [
          '#markup' => '<span class="verified-status' . (!empty($item->verified) ? ' verified' : '') . '">' . (!empty($item->verified) ? (string) t('Verified') : (string) t('Not verified')) . '</span>',
        ];
      }
    }

    return $element;
  }

}
