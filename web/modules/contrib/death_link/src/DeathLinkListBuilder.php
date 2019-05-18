<?php

namespace Drupal\death_link;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * Provides a listing of Death Link entities.
 */
class DeathLinkListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Death Link');
    $header['from_uri'] = $this->t('From');
    $header['to_uri'] = $this->t('To');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /* @var \Drupal\death_link\Entity\DeathLink $entity */
    $row['label'] = $entity->label();
    $row['from_uri'] = $entity->getFromUri();

    /* @var \Drupal\Core\Entity\EntityInterface $toEntity */
    $toEntity = $entity->getToEntity();

    $row['to_uri'] = '/';
    if ($toEntity) {
      $row['to_uri'] = Link::fromTextAndUrl($toEntity->label(), $toEntity->toUrl());
    }

    $row['status'] = $entity->status() ? $this->t('Active') : $this->t('Not Active');

    return $row + parent::buildRow($entity);
  }

}
