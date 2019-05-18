<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the snapshot view builders.
 */
class SnapshotViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'snapshot',
        'title' => t('Snapshot'),
        'open' => TRUE,
        'fields' => [
          'description',
          'snapshot_id',
          'volume_id',
          'size',
          'status',
          'progress',
          'encrypted',
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
