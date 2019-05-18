<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Image.
 */
class ImageListBuilder extends CloudContentListBuilder {

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
      ['data' => t('AMI Name'), 'specifier' => 'ami_name'],
      ['data' => t('AMI ID'), 'specifier' => 'image_id'],
      ['data' => t('Source'), 'specifier' => 'source'],
      ['data' => t('Owner'), 'specifier' => 'account_id'],
      ['data' => t('Visibility'), 'specifier' => 'visibility'],
      ['data' => t('Status'), 'specifier' => 'status'],
      ['data' => t('Architecture'), 'specifier' => 'architecture '],
      ['data' => t('Platform'), 'specifier' => 'platform'],
      ['data' => t('Root Device'), 'specifier' => 'root_device type'],
      ['data' => t('Virtualization'), 'specifier' => 'virtualization_type'],
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
        // @FIXME to use getInstanceId()
        // ->setRouteParameter('aws_cloud_instance', $entity->getInstanceId())
        ->setRouteParameter('aws_cloud_instance', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );

    $row['ami_name'] = $entity->getAmiName();
    $row['image_id'] = $entity->getImageId();
    $row['source'] = $entity->getSource();
    $row['account_id'] = $entity->getAccountId();
    $row['visibility'] = $entity->getVisibility();
    $row['status'] = $entity->getStatus();
    $row['architecture'] = $entity->getArchitecture();
    $row['platform'] = $entity->getPlatform();
    $row['root_device_type'] = $entity->getRootDeviceType();
    $row['virtualization_type'] = $entity->getVirtualizationType();

    return $row + parent::buildRow($entity);
  }

}
