<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the views data for the AWS Cloud Volume entity type.
 */
class VolumeViewsData extends Ec2BaseViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $table_name = $this->storage->getEntityTypeId();
    $fields = $this->entityManager->getFieldStorageDefinitions($table_name);

    // The following is a list of fields to turn from text search to
    // select list.  This list can be expanded through hook_views_data_alter().
    $selectable = [
      'size',
      'state',
      'volume_status',
      'volume_type',
      'iops',
      'availability_zone',
      'encrypted',
    ];

    $data['aws_cloud_volume']['table']['base']['access query tag'] = 'aws_cloud_volume_views_access';
    $this->addDropdownSelector($data, $table_name, $fields, $selectable);

    return $data;
  }

}
