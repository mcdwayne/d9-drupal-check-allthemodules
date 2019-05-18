<?php

namespace Drupal\measuremail;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of measuremail entities.
 *
 * @see \Drupal\measuremail\Entity\Measuremail
 */
class MeasuremailListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['subscription'] = $this->t('Subscription ID');
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['subscription'] = $entity->id();
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    return parent::getDefaultOperations($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['add_more'] = [
      '#title' => $this->t('Add a new Measuremail form'),
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.measuremail.add_form'),
      '#attributes' => [
        'class' => ['button', 'button-action', 'button--primary'],
      ],
    ];

    $build['table']['#empty'] = $this->t('There are currently forms. <a href=":url">Add one</a>.', [
      ':url' => Url::fromRoute('entity.measuremail.add_form')->toString(),
    ]);
    return $build;
  }

}
