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
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\DocValueField;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\StoreField;
use Drupal\elastic_search\Plugin\FieldMapperBase;

/**
 * Class Binary
 * Out of the box nothing directly supports this, as it must take a value as a
 * base64 encoded string And no core drupal field has this requirement
 *
 * @FieldMapper(
 *   id = "binary",
 *   label = @Translation("Binary")
 * )
 */
class Binary extends FieldMapperBase {

  use StringTranslationTrait;

  use DocValueField;
  use StoreField;

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
    return array_merge($this->getDocValueField($defaults[$this->getDocValueFieldId()]
                                               ??
                                               $this->getDocValueFieldDefault()),
                       $this->getStoreField($defaults[$this->getStoreFieldId()]
                                            ?? $this->getStoreFieldDefault()));
  }

}