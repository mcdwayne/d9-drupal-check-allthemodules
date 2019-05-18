<?php

namespace Drupal\mailing_list;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of mailing list entities.
 *
 * @see \Drupal\mailing_list\Entity\MailingList
 */
class MailingListListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Name');
    $header['description'] = [
      'data' => t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['label'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description']['data'] = ['#markup' => $entity->getDescription()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $operations['export'] = [
      'title' => $this->t('Export e-mail addresses'),
      'url' => Url::fromRoute('entity.mailing_list.export', ['mailing_list' => $entity->id()])
    ];
    $operations['import'] = [
      'title' => $this->t('Import e-mail addresses'),
      'url' => Url::fromRoute('entity.mailing_list.import', ['mailing_list' => $entity->id()])
    ];
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No mailing lists available. <a href=":link">Add mailing list</a>.', [
      ':link' => Url::fromRoute('mailing_list.list_add')->toString(),
    ]);

    return $build;
  }

}
