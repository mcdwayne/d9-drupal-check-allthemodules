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
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\IgnoreMalformedField;
use Drupal\elastic_search\Plugin\FieldMapperBase;

/**
 * Class NodeEntityMapper
 * This is special type of entity mapper, which will be used if a specific
 * class is not implemented for the type you are using
 *
 * @FieldMapper(
 *   id = "geo_point",
 *   label = @Translation("Geo Point")
 * )
 */
class GeoPoint extends FieldMapperBase {

  use StringTranslationTrait;

  use IgnoreMalformedField;

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return [];
  }

  /**
   * @inheritdoc
   */
  public function getFormFields(array $defaults, int $depth = 0): array {
    return $this->getIgnoreMalformedField($defaults[$this->getIgnoreMalformedFieldId()]
                                          ??
                                          $this->getIgnoreMalformedFieldDefault());
  }

}