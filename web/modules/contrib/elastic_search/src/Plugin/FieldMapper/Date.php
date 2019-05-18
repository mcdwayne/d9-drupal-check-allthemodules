<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\FieldMapper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elastic_search\Annotation\FieldMapper;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\BoostField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\DocValueField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IgnoreMalformedField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IncludeInAllField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IndexField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\NullValueField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\StoreField;
use Drupal\elastic_search\Plugin\FieldMapperBase;

/**
 * Class NodeEntityMapper
 * This is special type of entity mapper, which will be used if a specific
 * class is not implemented for the type you are using
 *
 * @FieldMapper(
 *   id = "date",
 *   label = @Translation("Date")
 * )
 */
class Date extends FieldMapperBase {

  use StringTranslationTrait;

  use BoostField;
  use DocValueField;
  use IndexField;
  use NullValueField;
  use StoreField;
  use IgnoreMalformedField;
  use IncludeInAllField;

  /**
   * @return array
   */
  public function getSupportedTypes() {
    return ['date', 'datetime', 'created', 'changed', 'timestamp'];
  }

  /**
   * @inheritdoc
   */
  public function getFormFields(array $defaults, int $depth = 0): array {

    $form = array_merge($this->getBoostField($defaults[$this->getBoostFieldId()]
                                             ?? $this->getBoostFieldDefault()),
                        $this->getDocValueField($defaults[$this->getDocValueFieldId()]
                                                ??
                                                $this->getDocValueFieldDefault()));

    $form['format'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Format'),
      '#description'   => $this->t('The date format(s) that can be parsed. Defaults to strict_date_optional_time||epoch_millis.'),
      '#options'       => $this->getFormatOptions(),
      '#multiple'      => TRUE,
      '#default_value' => $defaults['format'] ?? [
          'strict_date_optional_time' => 'strict_date_optional_time',
          'epoch_millis'              => 'epoch_millis',
        ],

    ];
    $form['locale'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Locale'),
      '#description'   => $this->t('The locale to use when parsing dates since months do not have the same names and/or abbreviations in all languages. The default is the ROOT locale'),
      '#default_value' => $defaults['locale'] ?? '',
    ];
    return array_merge($form,
                       $this->getIgnoreMalformedField($defaults[$this->getIgnoreMalformedFieldId()]
                                                      ??
                                                      $this->getIgnoreMalformedFieldDefault()),
                       $this->getIncludeInAllField($defaults[$this->getIncludeInAllFieldId()]
                                                   ??
                                                   $this->getIncludeInAllFieldDefault()),
                       $this->getIndexField($defaults[$this->getIndexFieldId()]
                                            ?? $this->getIndexFieldDefault()),
                       $this->getNullValueField($defaults[$this->getNullValueFieldId()]
                                                ??
                                                $this->getNullValueFieldId()),
                       $this->getStoreField($defaults[$this->getStoreFieldId()]
                                            ?? $this->getStoreFieldDefault()));
  }

  /**
   * @inheritDoc
   */
  public function getDslFromData(array $data): array {
    $data = parent::getDslFromData($data);
    if (array_key_exists('format', $data)) {
      $imp = implode('||', $data['format']);
      $data['format'] = $imp;
    }
    return $data;
  }

  /**
   * @return array
   */
  private function getFormatOptions() {
    return array_combine(self::$options, self::$options);
  }

  /**
   * @var array
   */
  static private $options = [
    'epoch_millis',
    'epoch_second',
    'date_optional_time',
    'strict_date_optional_time',
    'basic_date',
    'basic_date_time',
    'basic_date_time_no_millis',
    'basic_ordinal_date',
    'basic_ordinal_date_time',
    'basic_ordinal_date_time_no_millis',
    'basic_time',
    'basic_time_no_millis',
    'basic_t_time',
    'basic_t_time_no_millis',
    'basic_week_date',
    'strict_basic_week_date',
    'basic_week_date_time',
    'strict_basic_week_date_time',
    'basic_week_date_time_no_millis',
    'strict_basic_week_date_time_no_millis',
    'date',
    'strict_date',
    'date_hour',
    'strict_date_hour',
    'date_hour_minute',
    'strict_date_hour_minute',
    'date_hour_minute_second',
    'strict_date_hour_minute_second',
    'date_hour_minute_second_fraction',
    'strict_date_hour_minute_second_fraction',
    'date_hour_minute_second_millis',
    'strict_date_hour_minute_second_millis',
    'date_time',
    'strict_date_time',
    'date_time_no_millis',
    'strict_date_time_no_millis',
    'hour',
    'strict_hour',
    'hour_minute',
    'strict_hour_minute',
    'hour_minute_second',
    'strict_hour_minute_second',
    'hour_minute_second_fraction',
    'strict_hour_minute_second_fraction',
    'hour_minute_second_millis',
    'strict_hour_minute_second_millis',
    'ordinal_date',
    'strict_ordinal_date',
    'ordinal_date_time',
    'strict_ordinal_date_time',
    'ordinal_date_time_no_millis',
    'strict_ordinal_date_time_no_millis',
    'time',
    'strict_time',
    'time_no_millis',
    'strict_time_no_millis',
    't_time',
    'strict_t_time',
    't_time_no_millis',
    'strict_t_time_no_millis',
    'week_date',
    'strict_week_date',
    'week_date_time',
    'strict_week_date_time',
    'week_date_time_no_millis',
    'strict_week_date_time_no_millis',
    'weekyear',
    'strict_weekyear',
    'weekyear_week',
    'strict_weekyear_week',
    'weekyear_week_day',
    'strict_weekyear_week_day',
    'year',
    'strict_year',
    'year_month',
    'strict_year_month',
    'year_month_day',
    'strict_year_month_day',
  ];

}