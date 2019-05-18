<?php

namespace Drupal\ip_ranges;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

class IPRangesListBuilder extends EntityListBuilder {

  /**
   * Constructs a new UserRestrictionsListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Banned IP addresses');
    $header['type'] = t('List type');
    $header['description'] = t('IP Range description');

    return $header + parent::buildHeader();
  }

// @TODO await resolution of https://drupal.org/node/2274011
//  public function emptyText() {
//    return $this->t('There are no @label yet.', array('@label' => $this->entityType->getLabel()));
//  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->getIpDisplay();
    $row['type'] = $entity->getType() ? t('Whitelist') : t('<strong>Blacklist</strong>');
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'ip_ranges_admin_form';
  }

}
