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
 * The integer type not only allows numerical fields to be mapped, but is used
 * for many fields where you may store a reference.
 * It is not always practical to internally map a references object and
 * therefore the integer field is also used to deal with this
 *
 * @FieldMapper(
 *   id = "integer",
 *   label = @Translation("Integer")
 * )
 */
class Integer extends FieldMapperBase {

  use NumericTypeFormFieldsTrait;

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return ['integer', 'duration'];
  }

}