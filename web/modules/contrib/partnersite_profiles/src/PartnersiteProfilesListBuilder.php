<?php

namespace Drupal\partnersite_profile;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Partnersite profiles entities.
 */
class PartnersiteProfilesListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Partnersite profiles');
    $header['id'] = $this->t('Machine name');
    $header['auth_div'] = $this->t( 'Authentication Division');
    $header['auth_secret'] = $this->t( 'Authentication Secret');
    $header['auth_mapping_hash'] = $this->t( 'Authentication Hash');
    $header['auth_timestamp_expiry'] = $this->t( 'Expiry Time');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['auth_div'] = $entity->getAuthDiv();
    $row['auth_secret'] = $entity->getAuthSecret();
    $row['auth_mapping_hash'] = $entity->getAuthMappingHash();
		$row['auth_timestamp_expiry'] = $entity->getAuthTimestampExpiry();
    return $row + parent::buildRow($entity);
  }

}
