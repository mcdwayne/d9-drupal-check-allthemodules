<?php

namespace Drupal\memory_limit_policy\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Memory Limit Policies.
 */
class MemoryLimitPolicyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'memory_limit_policy_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Memory Limit Policy');
    $header['id'] = $this->t('Machine name');
    $header['memory'] = $this->t('Memory');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = [
      '#markup' => $entity->id(),
    ];
    $row['memory'] = [
      '#markup' => $entity->getMemory(),
    ];
    $row['status'] = [
      '#markup' => $entity->status() ? $this->t('Enabled') : $this->t('Disabled'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['edit']['url'] = new Url('entity.memory_limit_policy.wizard.edit', ['machine_name' => $entity->id(), 'step' => 'general']);

    // @TODO: Check how the route is built.
    unset($operations['translate']);

    return $operations;
  }

}
