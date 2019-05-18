<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the instance view builders.
 */
class InstanceViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'instance',
        'title' => t('Instance'),
        'open' => TRUE,
        'fields' => [
          'instance_id',
          'name',
          'instance_type',
          'cost',
          'iam_role',
          'instance_state',
          'image_id',
          'kernel_id',
          'ramdisk_id',
          'virtualization',
          'reservation',
          'account_id',
          'launch_time',
        ],
      ],
      [
        'name' => 'network',
        'title' => t('Network'),
        'open' => TRUE,
        'fields' => [
          'network_interfaces',
          'public_ip',
          'private_ips',
          'public_dns',
          'security_groups',
          'key_pair_name',
          'vpc_id',
          'subnet_id',
          'availability_zone',
        ],
      ],
      [
        'name' => 'storage',
        'title' => t('Storage'),
        'open' => TRUE,
        'fields' => [
          'root_device_type',
          'root_device',
          'ebs_optimized',
        ],
      ],
      [
        'name' => 'fieldset_tags',
        'title' => t('Tags'),
        'open' => TRUE,
        'fields' => [
          'tags',
        ],
      ],
      [
        'name' => 'options',
        'title' => t('Options'),
        'open' => TRUE,
        'fields' => [
          'termination_protection',
          'is_monitoring',
          'ami_launch_index',
          'tenancy',
          'termination_timestamp',
          'schedule',
        ],
      ],
      [
        'name' => 'others',
        'title' => t('Others'),
        'open' => FALSE,
        'fields' => [
          'cloud_context',
          'uid',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldValue(EntityInterface $entity, $field_name, $default_value) {
    $value = parent::getFieldValue($entity, $field_name, $default_value);
    if ($field_name == 'iam_role' && $value != NULL && $value->value != NULL) {
      $roles = aws_cloud_get_iam_roles($entity->getCloudContext());
      return $roles[$value->value];
    }

    return $value;
  }

}
