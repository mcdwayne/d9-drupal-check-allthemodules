<?php

namespace Drupal\jsonapi_include\Normalizer\Value;

use Drupal\Component\Utility\NestedArray;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\HttpExceptionNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\JsonApiDocumentTopLevelNormalizerValue as JsonApiDocumentTopLevelNormalizerValueBase;

/**
 * Helps normalize the top level document in compliance with the JSON API spec.
 */
class JsonApiDocumentTopLevelNormalizerValue extends JsonApiDocumentTopLevelNormalizerValueBase {

  /**
   * @inheritdoc
   */
  public function rasterizeValue() {
    $rasterized = parent::rasterizeValue();
    if ($this->isCollection && !empty($rasterized['included']) && !empty($rasterized['data'])) {
      $included_data = [];
      foreach ($rasterized['included'] as $item) {
        $included_data[$item['id']] = $item;
      }
      foreach ($rasterized['data'] as &$item) {
        foreach ($item['relationships'] as &$relationship) {
          if (isset($relationship['data'][0])) {
            foreach ($relationship['data'] as &$relation_item) {
              $id = $relation_item['id'];
              if (isset($included_data[$id])) {
                $relation_item['data']['attributes'] = $included_data[$id]['attributes'];
              }
            }
          }
          else {
            $id = $relationship['data']['id'];
            if (isset($included_data[$id])) {
              $relationship['data']['attributes'] = $included_data[$id]['attributes'];
            }
          }
        }
      }
    }
    return $rasterized;
  }
}
