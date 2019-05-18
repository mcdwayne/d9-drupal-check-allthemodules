<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the volume view builders.
 */
class VolumeViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'volume',
        'title' => t('Volume'),
        'open' => TRUE,
        'fields' => [
          'attachment_information',
          'volume_id',
          'snapshot_id',
          'snapshot_name',
          'size',
          'availability_zone',
          'volume_type',
          'iops',
          'encrypted',
          'state',
          'created',
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

}
