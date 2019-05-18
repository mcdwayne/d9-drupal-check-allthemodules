<?php

namespace Drupal\commerce_installments;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for payment gateways.
 */
class InstallmentPlanMethodListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'methods';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_installment_methods';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Installment plan');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface $entity */
    $status = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $row['label'] = $entity->label();
    // $this->weightKey determines whether the table will be rendered as a form.
    if (!empty($this->weightKey)) {
      $row['status']['#markup'] = $status;
    }
    else {
      $row['status'] = $status;
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are less than 2 gateways, disable dragging.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    return parent::render();
  }

}
