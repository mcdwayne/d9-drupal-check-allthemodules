<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the elastic ip view builders.
 */
class ElasticIpViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'ip_address',
        'title' => t('IP Address'),
        'open' => TRUE,
        'fields' => [
          'public_ip',
          'private_ip_address',
        ],
      ],
      [
        'name' => 'assign',
        'title' => t('Assign'),
        'open' => TRUE,
        'fields' => [
          'instance_id',
          'network_interface_id',
          'allocation_id',
          'association_id',
          'domain',
          'network_interface_owner',
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
