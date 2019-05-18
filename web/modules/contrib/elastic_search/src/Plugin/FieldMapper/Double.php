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
 *   id = "double",
 *   label = @Translation("Double")
 * )
 */
class Double extends FieldMapperBase {

  use NumericTypeFormFieldsTrait;

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return ['decimal'];
  }

}