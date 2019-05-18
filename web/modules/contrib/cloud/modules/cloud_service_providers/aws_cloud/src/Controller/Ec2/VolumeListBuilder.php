<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Volume.
 */
class VolumeListBuilder extends CloudContentListBuilder {

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
      ['data' => t('Volume ID'), 'specifier' => 'volume_id'],
      ['data' => t('Size'), 'specifier' => 'size'],
      ['data' => t('Volume Type'), 'specifier' => 'volume_type'],
      ['data' => t('IOPS'), 'specifier' => 'iops'],
      ['data' => t('Snapshot'), 'specifier' => 'snapshot_id'],
      ['data' => t('Created'), 'specifier' => 'created'],
      ['data' => t('Zone'), 'specifier' => 'availability_zone'],
      ['data' => t('State'), 'specifier' => 'state'],
      ['data' => t('Alarm Status'), 'specifier' => 'alarm_status'],
      ['data' => t('Attachment Information'), 'specifier' => 'attachment_information'],
      ['data' => t('Volume Status'), 'specifier' => 'volume_status'],
      ['data' => t('Encrypted'), 'specifier' => 'encrypted'],
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
        ->setRouteParameter('aws_cloud_volume', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );
    $row['volume_id'] = $entity->getVolumeId();
    $row['size'] = $entity->getSize();
    $row['volume_type'] = $entity->getVolumeType();
    $row['iops'] = $entity->getIops();
    $row['snapshot_id'] = $entity->getSnapshotId();
    $row['created'] = date('Y/m/d H:i', $entity->created());
    $row['availability_zone'] = $entity->getAvailabilityZone();
    $row['state'] = $entity->getState();
    $row['alarm_status'] = $entity->getAlarmStatus();
    $row['attachment_information'] = $entity->getAttachmentInformation();
    $row['volume_status'] = $entity->getVolumeStatus();
    $row['encrypted'] = $entity->getEncrypted();

    return $row + parent::buildRow($entity);
  }

}
