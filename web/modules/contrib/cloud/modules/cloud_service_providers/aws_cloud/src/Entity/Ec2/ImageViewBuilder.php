<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the image view builders.
 */
class ImageViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'image',
        'title' => t('Image'),
        'open' => TRUE,
        'fields' => [
          'name',
          'description',
          'ami_name',
          'image_id',
          'instance_id',
          'account_id',
          'source',
          'status',
          'state_reason',
          'created',
        ],
      ],
      [
        'name' => 'type',
        'title' => t('Type'),
        'open' => TRUE,
        'fields' => [
          'platform',
          'architecture',
          'virtualization_type',
          'product_code',
          'image_type',
        ],
      ],
      [
        'name' => 'device',
        'title' => t('Device'),
        'open' => TRUE,
        'fields' => [
          'root_device_name',
          'root_device_type',
          'kernel_id',
          'ramdisk_id',
          'block_devices',
        ],
      ],
      [
        'name' => 'others',
        'title' => t('Others'),
        'open' => FALSE,
        'fields' => [
          'cloud_context',
          'visibility',
          'uid',
        ],
      ],
    ];
  }

}
