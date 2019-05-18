<?php

namespace Drupal\dcat\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'accrual_periodicity' formatter.
 *
 * @FieldFormatter(
 *   id = "accrual_periodicity",
 *   label = @Translation("Accrual periodicity label"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class AccrualPeriodicityFormatter extends FormatterBase {

  /**
   * Returns the conversion list.
   *
   * @return array
   *   Conversion list.
   */
  public static function conversionList() {
    return [
      'http://purl.org/cld/freq/triennial' => t('Triennial'),
      'http://purl.org/cld/freq/biennial' => t('Biennial'),
      'http://purl.org/cld/freq/annual' => t('Annual'),
      'http://purl.org/cld/freq/semiannual' => t('Semiannual'),
      'http://purl.org/cld/freq/threeTimesAYear' => t('Three times a year'),
      'http://purl.org/cld/freq/quarterly' => t('Quarterly'),
      'http://purl.org/cld/freq/bimonthly' => t('Bimonthly'),
      'http://purl.org/cld/freq/monthly' => t('Monthly'),
      'http://purl.org/cld/freq/semimonthly' => t('Semimonthly'),
      'http://purl.org/cld/freq/biweekly' => t('Biweekly'),
      'http://purl.org/cld/freq/threeTimesAMonth' => t('Three times a month'),
      'http://purl.org/cld/freq/weekly' => t('Weekly'),
      'http://purl.org/cld/freq/semiweekly' => t('Semiweekly'),
      'http://purl.org/cld/freq/threeTimesAWeek' => t('Three times a week'),
      'http://purl.org/cld/freq/daily' => t('Daily'),
      'http://purl.org/cld/freq/continuous' => t('Continuous'),
      'http://purl.org/cld/freq/completelyIrregular' => t('Irregular'),
      'http://purl.org/cld/freq/irregular' => t('Irregular'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => isset(self::conversionList()[$item->value]) ? self::conversionList()[$item->value] : $item->value,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition);
  }

}