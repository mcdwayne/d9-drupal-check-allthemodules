<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\FieldMapper;

use Drupal\elastic_search\Annotation\FieldMapper;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\NumericTypeFormFieldsTrait;
use Drupal\elastic_search\Plugin\FieldMapperBase;

/**
 * Class NodeEntityMapper
 * This is special type of entity mapper, which will be used if a specific
 * class is not implemented for the type you are using
 *
 * @FieldMapper(
 *   id = "scaled_float",
 *   label = @Translation("Float")
 * )
 */
class ScaledFloat extends FieldMapperBase {

  use NumericTypeFormFieldsTrait {
    getFormFields as numericGetFormFields;
  }

  /**
   * @return array
   */
  public function getSupportedTypes() {
    return ['decimal'];
  }

  /**
   * @param array $defaults
   *
   * @return array
   */
  public function getFormFields(array $defaults, int $depth = 0): array {
    $form = $this->numericGetFormFields($defaults);
    $form['scaling_factor'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Scaling Value'),
      '#description'   => $this->t('The scaling factor to use when encoding values. Values will be multiplied by this factor at index time and rounded to the closest long value. For instance, a scaled_float with a scaling_factor of 10 would internally store 2.34 as 23 and all search-time operations (queries, aggregations, sorting) will behave as if the document had a value of 2.3. High values of scaling_factor improve accuracy but also increase space requirements. This parameter is required.'),
      '#default_value' => $defaults['scaling_factor'] ?? 0,
      '#required'      => TRUE,
    ];
    return $form;
  }

}