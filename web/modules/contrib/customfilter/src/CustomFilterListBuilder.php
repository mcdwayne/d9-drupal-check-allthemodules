<?php

namespace Drupal\customfilter;

// Load the class used as base.
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
// Load the interface for entities.
use Drupal\Core\Entity\EntityInterface;

// Necessary to create links.
use Drupal\Core\Url;

/**
 * Provides a listing of Custom Filters.
 */
class CustomFilterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Filter');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = \Drupal::l($this->getLabel($entity), URL::fromRoute('customfilter.rules.list', array('customfilter' => $entity->id())));
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }
}
