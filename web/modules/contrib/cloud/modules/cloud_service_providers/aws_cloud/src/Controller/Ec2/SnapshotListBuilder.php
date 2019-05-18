<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Snapshot.
 */
class SnapshotListBuilder extends CloudContentListBuilder {

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
      ['data' => t('Snapshot ID'), 'specifier' => 'snapshot_id'],
      ['data' => t('Size'), 'specifier' => 'size'],
      ['data' => t('Description'), 'specifier' => 'description'],
      ['data' => t('Status'), 'specifier' => 'status'],
      ['data' => t('Started'), 'specifier' => 'started'],
      ['data' => t('Progress'), 'specifier' => 'progress'],
      ['data' => t('Encrypted'), 'specifier' => 'encrypted'],
      ['data' => t('KMS Key ID'), 'specifier' => 'kms_key_id'],
      ['data' => t('KMS Key Alias'), 'specifier' => 'kms_key_aliases'],
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
        // @FIXME to use getSnapshotId()
        // ->setRouteParameter('aws_cloud_snapshot', $entity->getSnapshotId()  )
        ->setRouteParameter('aws_cloud_snapshot', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );
    $row['snapshot_id'] = $entity->getSnapshotId();
    $row['size'] = $entity->getSize();
    $row['description'] = $entity->getDescription();
    $row['status'] = $entity->getStatus();
    $row['started'] = date('Y/m/d H:i', $entity->getStarted());
    $row['progress'] = $entity->getProgress();
    $row['encrypted'] = $entity->getEncrypted();
    $row['kms_key_id'] = $entity->getKmsKeyId();
    $row['kms_key_aliases'] = $entity->getKmsKeyAliases();

    return $row + parent::buildRow($entity);
  }

}
