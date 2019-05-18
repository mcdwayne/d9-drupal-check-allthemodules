<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the network interface view builders.
 */
class NetworkInterfaceViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'network_interface',
        'title' => t('Network Interface'),
        'open' => TRUE,
        'fields' => [
          'description',
          'network_interface_id',
          'instance_id',
          'allocation_id',
          'mac_address',
          'device_index',
          'status',
          'delete_on_termination',
        ],
      ],
      [
        'name' => 'network',
        'title' => t('Network'),
        'open' => TRUE,
        'fields' => [
          'security_groups',
          'vpc_id',
          'subnet_id',
          'public_ips',
          'primary_private_ip',
          'secondary_private_ips',
          'private_dns',
        ],
      ],
      [
        'name' => 'attachment',
        'title' => t('Attachment'),
        'open' => FALSE,
        'fields' => [
          'attachment_id',
          'attachment_owner',
          'attachment_status',
        ],
      ],
      [
        'name' => 'owner',
        'title' => t('Owner'),
        'open' => FALSE,
        'fields' => ['account_id'],
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
