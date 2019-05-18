<?php

namespace Drupal\bueditor;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a list of BUEditor Button entities.
 *
 * @see \Drupal\bueditor\Entity\BUEditorButton
 */
class BUEditorButtonListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = ['data' => $this->t('ID'), 'class' => 'button-id'];
    $header['label'] = ['data' => $this->t('Name'), 'class' => 'button-label'];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $bueditor_button) {
    $row['id'] = $bueditor_button->id();
    $row['label'] = $bueditor_button->label();
    return $row + parent::buildRow($bueditor_button);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $bueditor_button) {
    $operations = parent::getDefaultOperations($bueditor_button);
    $operations['duplicate'] = [
      'title' => t('Duplicate'),
      'weight' => 15,
      'url' => $bueditor_button->toUrl('duplicate-form'),
    ];
    return $operations;
  }

}
