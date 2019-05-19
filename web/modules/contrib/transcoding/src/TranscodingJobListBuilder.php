<?php

namespace Drupal\transcoding;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Transcoding job entities.
 *
 * @ingroup transcoding
 */
class TranscodingJobListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Transcoding job ID');
    $header['service'] = $this->t('Service');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\transcoding\Entity\TranscodingJob */
    $row['id'] = $entity->id();
    $row['service'] = $entity->get('service')->first()->getString();
    $row['status'] = $entity->get('status')->first()->getString();
    return $row + parent::buildRow($entity);
  }

}
