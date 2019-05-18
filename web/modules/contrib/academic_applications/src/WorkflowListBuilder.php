<?php

namespace Drupal\academic_applications;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of workflow entities.
 */
class WorkflowListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Workflow');
    $header['apply'] = $this->t('Apply form');
    $header['upload'] = $this->t('Upload form');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $apply_form_config = \Drupal::configFactory()->get($entity->getApplication());
    $row['apply'] = $apply_form_config->get('title');
    $upload_form_config = \Drupal::configFactory()->get($entity->getUpload());
    $row['upload'] = $upload_form_config->get('title');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no @label yet.', ['@label' => $this->entityType->getLabel()]);
    return $build;
  }

}
