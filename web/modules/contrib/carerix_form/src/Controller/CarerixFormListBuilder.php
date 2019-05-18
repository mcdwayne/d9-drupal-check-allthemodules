<?php

namespace Drupal\carerix_form\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\carerix_form\CarerixFormFieldsOpen;

/**
 * Provides a listing of Carerix forms.
 *
 * @see \Drupal\carerix_form\Entity\CarerixForm
 */
class CarerixFormListBuilder extends ConfigEntityListBuilder {

  /**
   * Not to be deleted Carerix forms.
   *
   * @var array
   */
  protected $permanentIds = [
    CarerixFormFieldsOpen::NAME,
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Carerix form');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row += parent::buildRow($entity);

    if (in_array($row['id'], $this->permanentIds)) {
      // Disable delete link.
      unset($row['operations']['data']['#links']['delete']);
    }

    return $row;
  }

}
