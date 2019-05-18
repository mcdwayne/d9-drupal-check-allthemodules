<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\cloud\Controller\CloudContentListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of KeyPair.
 */
class KeyPairListBuilder extends CloudContentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      // field should be 'field', not 'specifier' in ConfigEntity.
      [
        'data' => t('Key Pair Name'),
        'specifier' => 'key_pair_name',
        'sort' => 'ASC',
      ],
      ['data' => t('Key Fingerprint'), 'specifier' => 'fingerprint'],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['key_pair_name'] = \Drupal::l(
      $entity->getKeyPairName(),
      $entity->urlInfo('canonical')
        ->setRouteParameter('aws_cloud_key_pair', $entity->id())
        ->setRouteParameter('cloud_context', $entity->getCloudContext())
    );
    $row['key_fingerprint'] = $entity->getKeyFingerprint();

    return $row + parent::buildRow($entity);
  }

}
