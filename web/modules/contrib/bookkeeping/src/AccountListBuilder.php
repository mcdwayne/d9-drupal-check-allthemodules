<?php

namespace Drupal\bookkeeping;

use Drupal\bookkeeping\Entity\Account;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Account entities.
 */
class AccountListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'accounts';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bookkeeping_accounts_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Account');
    $header['id'] = $this->t('ID');
    $header['code'] = $this->t('Code');
    $header['department'] = $this->t('Department');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\bookkeeping\Entity\AccountInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['#type'] = $entity->getType();
    $row['code'] = $entity->getCode();
    $row['department'] = $entity->getDepartment();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    if (count($build['table']['#rows'])) {
      $rows = $build['table']['#rows'];
      $build['table']['#rows'] = [];

      $type_labels = Account::getTypeOptions();
      $current_type = NULL;
      $columns = count(reset($rows));
      foreach ($rows as $key => $row) {
        if ($current_type != $row['#type']) {
          $current_type = $row['#type'];
          $build['table']['#rows']["_{$current_type}"] = [
            'type' => [
              'colspan' => $columns,
              'data' => ['#markup' => $type_labels[$row['#type']]],
              'header' => TRUE,
            ],
          ];
        }
        unset($row['#type']);
        $build['table']['#rows'][$key] = $row;
      }
    }

    return $build;
  }

}
