<?php

namespace Drupal\media_entity_usage\Plugin\views\field;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity_usage\Service\MediaUsageInfo;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class MediaUsageCountField
 *
 * @package Drupal\media_entity_usage\Plugin\views\field
 *
 * @ingroup views_field_handlers
 * @ViewsField("media_usage")
 */
class MediaUsageCountField extends FieldPluginBase {

  public function query() {}

  /**
   * @param ResultRow $values
   *
   * @return array|null
   */
  public function render(ResultRow $values) {
    $media = $values->_entity;
    if ($media instanceof MediaInterface) {
      /** @var MediaUsageInfo $info */
      $info = \Drupal::service('media_entity_usage.reference_info');
      $result = $info->getRefsCount($media);
      return [
        "#markup" => '<span class="media-usage">' . $result . '</span>'
      ];
    }
    return null;
  }
}