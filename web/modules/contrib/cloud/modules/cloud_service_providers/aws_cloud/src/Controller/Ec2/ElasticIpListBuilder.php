<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ElasticIp.
 */
class ElasticIpListBuilder extends CloudContentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      // field should be 'field', not 'specifier' in ConfigEntity.
      ['data' => t('Name'), 'specifier' => 'name', 'sort' => 'ASC'],
      ['data' => t('Elastic IP'), 'specifier' => 'public_ip'],
      ['data' => t('Allocation ID'), 'specifier' => 'allocation_id'],
      ['data' => t('Instance'), 'specifier' => 'instance_id'],
      ['data' => t('Private IP Address'), 'specifier' => 'private_ip_address'],
      ['data' => t('Scope'), 'specifier' => 'scope'],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['name'] = \Drupal::l(
      $this->getLabel($entity),
      $entity->urlInfo('canonical')
        // @FIXME to use getImageId()
        // ->setRouteParameter('aws_cloud_elastic_ip', $entity->getElasticIp())
        ->setRouteParameter('aws_cloud_elastic_ip', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );
    $row['public_ip'] = $entity->getPublicIp();
    $row['allocation_id'] = $entity->getAllocationId();
    $row['instance_id'] = $entity->getInstanceId();
    $row['private_ip_address'] = $entity->getPrivateIpAddress();
    $row['scope'] = $entity->getScope();

    return $row + parent::buildRow($entity);
  }

}
