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
use Drupal\elastic_search\Plugin\FieldMapperInterface;

/**
 * Class NodeEntityMapper
 * This is special type of entity mapper, which will be used if a specific
 * class is not implemented for the type you are using.
 * IIf none is selected it means that this field will be excluded from indexing
 *
 * @FieldMapper(
 *   id = "none",
 *   label = @Translation("None")
 * )
 */
class None extends FieldMapperBase {

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return [FieldMapperInterface::ALWAYS_AVAILABLE];
  }

}