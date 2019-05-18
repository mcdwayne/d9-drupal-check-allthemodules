<?php

namespace Drupal\field_list_details;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field_ui\FieldConfigListBuilder;

/**
 * Class FieldListDetailsFieldConfigListBuilder.
 *
 * @package Drupal\field_list_details
 */
class FieldListDetailsFieldConfigListBuilder extends FieldConfigListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    $row = parent::buildRow($field_config);

    if (!$field_config instanceof ThirdPartySettingsInterface) {
      return $row;
    }

    $collection = new FieldListDetailsCollection($field_config);
    $details = $collection->getDetails();
    unset($details['field_name']);

    if (!empty($details)) {
      $row['data']['label'] = [
        'data' => [
          '#theme' => 'field_list_details_list',
          '#label' => $field_config->label(),
          '#details' => $details,
          '#attributes' => [
            'class' => ['field-list-details-list'],
          ],
        ],
      ];
    }

    return $row;
  }

}
