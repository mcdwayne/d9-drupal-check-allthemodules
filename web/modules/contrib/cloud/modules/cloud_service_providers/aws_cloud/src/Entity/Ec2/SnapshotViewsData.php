<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the views data for the CloudScripting entity type.
 */
class SnapshotViewsData extends Ec2BaseViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['aws_cloud_snapshot']['table']['base'] = [
      'field' => 'id',
      'title' => t('AWS Cloud Snapshot'),
      'help'  => t('The AWC Cloud Snapshot entity ID.'),
    ];

    $table_name = $this->storage->getEntityTypeId();
    $fields = $this->entityManager->getFieldStorageDefinitions($table_name);

    // The following is a list of fields to turn from text search to
    // select list.  This list can be expanded through hook_views_data_alter().
    $selectable = [
      'size',
      'volume_id',
      'account_id',
      'encrypted',
      'capacity',
    ];

    $data['aws_cloud_snapshot']['table']['base']['access query tag'] = 'aws_cloud_snapshot_views_access';
    $this->addDropdownSelector($data, $table_name, $fields, $selectable);

    return $data;
  }

}
