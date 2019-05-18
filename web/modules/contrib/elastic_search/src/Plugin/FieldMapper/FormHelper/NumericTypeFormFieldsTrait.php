<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 15:00
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Trait IntegerFieldFormFieldsTrait
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper
 */
trait NumericTypeFormFieldsTrait {

  use BoostField;
  use IndexField;
  use DocValueField;
  use StoreField;
  use NullValueField;
  use IncludeInAllField;
  use IgnoreMalformedField;

  use StringTranslationTrait;

  /**
   * @param array $defaults
   *
   * @return array
   */
  public function getFormFields(array $defaults, int $depth = 0): array {
    $form = array_merge($this->getBoostField($defaults[$this->getBoostFieldId()]
                                             ?? $this->getBoostFieldDefault()),
                        $this->getDocValueField($defaults[$this->getDocValueFieldId()]
                                                ??
                                                $this->getDocValueFieldDefault()),
                        $this->getIndexField($defaults[$this->getIndexFieldId()]
                                             ?? $this->getIndexFieldDefault()),
                        $this->getNullValueField($defaults[$this->getNullValueFieldId()]
                                                 ??
                                                 $this->getNullValueFieldDefault()),
                        $this->getStoreField($defaults[$this->getStoreFieldId()]
                                             ?? $this->getStoreFieldDefault()));
    $form['coerce'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Coerce'),
      '#description'   => $this->t('Coerce stings to numbers and truncate fractions for integers'),
      '#default_value' => TRUE,
    ];

    $form = array_merge($form,
                        $this->getIgnoreMalformedField($defaults[$this->getIgnoreMalformedFieldId()]
                                                       ?? 0),
                        $this->getIncludeInAllField($defaults[$this->getIncludeInAllFieldId()]
                                                    ??
                                                    $defaults[$this->getIndexFieldId()]
                                                    ?? TRUE));
    return $form;
  }

}