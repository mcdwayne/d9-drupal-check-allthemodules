<?php

namespace Drupal\bueditor;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a list of BUEditor Editor entities.
 *
 * @see \Drupal\bueditor\Entity\BUEditorEditor
 */
class BUEditorEditorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    $header['toolbar'] = $this->t('Toolbar');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $bueditor_editor) {
    $row['label'] = $bueditor_editor->label();
    $row['description'] = $bueditor_editor->get('description');
    $row['toolbar'] = implode(', ', $bueditor_editor->getToolbar());
    return $row + parent::buildRow($bueditor_editor);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $bueditor_editor) {
    $operations = parent::getDefaultOperations($bueditor_editor);
    $operations['duplicate'] = [
      'title' => t('Duplicate'),
      'weight' => 15,
      'url' => $bueditor_editor->toUrl('duplicate-form'),
    ];
    return $operations;
  }

}
