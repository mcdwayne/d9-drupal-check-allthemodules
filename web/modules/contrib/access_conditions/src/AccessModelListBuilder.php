<?php

namespace Drupal\access_conditions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of access model entities.
 *
 * @see \Drupal\access_conditions\Entity\AccessModel
 */
class AccessModelListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\access_conditions\Entity\AccessModelInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $t_args = [':link' => Url::fromRoute('entity.access_model.add_form')->toString()];
    $build['table']['#empty'] = $this->t('No access model available. <a href=":link">Add access model</a>.', $t_args);

    return $build;
  }

}
