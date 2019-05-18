<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of SecurityGroup.
 */
class SecurityGroupListBuilder extends CloudContentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      // field should be 'field', not 'specifier' in ConfigEntity.
      ['data' => t('Group Name'), 'specifier' => 'group_name'],
      ['data' => t('Group ID'), 'specifier' => 'group_id'],
      ['data' => t('VPC ID'), 'specifier' => 'vpc_id'],
      ['data' => t('Description'), 'specifier' => 'description'],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['group_name'] = \Drupal::l(
      $entity->getGroupName(),
      $entity->urlInfo('canonical')
        ->setRouteParameter('aws_cloud_security_group', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );
    $row['group_id'] = $entity->getGroupId();
    $row['vpc_id'] = $entity->getVpcId();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

}
