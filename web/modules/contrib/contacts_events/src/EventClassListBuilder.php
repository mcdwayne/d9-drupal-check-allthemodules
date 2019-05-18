<?php

namespace Drupal\contacts_events;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Class entities.
 */
class EventClassListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_events_class_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Class');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\contacts_events\Entity\EventClassInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = ['#markup' => $entity->id()];
    $row['status'] = [
      '#markup' => $entity->status() ? $this->t('Enabled') : $this->t('Disabled'),
    ];
    $row['type'] = ['#markup' => $entity->get('type')];
    return $row + parent::buildRow($entity);
  }

}
