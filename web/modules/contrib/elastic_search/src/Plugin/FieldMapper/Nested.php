<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12/10/16
 * Time: 13:21
 */

namespace Drupal\elastic_search\Plugin\FieldMapper;

use Drupal\elastic_search\Annotation\FieldMapper;
use Drupal\elastic_search\Plugin\FieldMapperBase;

/**
 * Class Nested
 * This exists but is not made available to select as we use the fields
 * cardinality to determine if it should be a nested type or not
 *
 * @FieldMapper(
 *   id = "nested",
 *   label = @Translation("Nested Objects")
 * )
 */
class Nested extends FieldMapperBase {

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return [];
  }

}