<?php

namespace Drupal\external_link_popup\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Example.
 */
class ExternalLinkPopupListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['id'] = $this->t('Machine name');
    $header['domains'] = $this->t('Domains');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id']['#markup'] = $entity->id();
    $row['domains']['#markup'] = $entity->getDomains();
    if ($entity->status()) {
      $row['status']['#markup'] = $this->t('Enabled');
    }
    else {
      $row['status']['#markup'] = $this->t('Disabled');
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_link_popup_entity_list_form';
  }

}
