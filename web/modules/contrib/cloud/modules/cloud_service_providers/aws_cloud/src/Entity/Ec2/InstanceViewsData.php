<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the views data for the CloudScripting entity type.
 */
class InstanceViewsData extends Ec2BaseViewsData {

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
      'instance_state',
      'instance_type',
      'availability_zone',
      'key_pair_name',
      'security_groups',
      'vpc_id',
      'subnet_id',
      'image_id',
    ];

    // Add an access query tag.
    $data[$table_name]['table']['base']['access query tag'] = 'aws_cloud_instance_views_access';
    $this->addDropdownSelector($data, $table_name, $fields, $selectable);

    return $data;
  }

}
