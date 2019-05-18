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
 * This needs a lot more work before it becomes usable
 *
 * @FieldMapper(
 *   id = "geo_shape",
 *   label = @Translation("Geo Shape")
 * )
 */
class GeoShape extends FieldMapperBase {

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return [];
  }

}