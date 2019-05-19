<?php

namespace Drupal\simple_access\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Drupal\simple_access\Controller\SimpleAccessGroupListBuilder.
 */
class SimpleAccessGroupListBuilder extends DraggableListBuilder {

  protected $weightKey = 'weight';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_access_group_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];

    $header['label'] = $this->t('Group');
    $header['roles'] = $this->t('Roles');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['label'] = $entity->label();

    $row['roles'] = [
      '#type' => 'markup',
      '#markup' => implode(', ', array_intersect_key(user_role_names(), array_filter($entity->roles))),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Unset the weight for owner as it is always the highest value.
    $form_state->unsetValue('entities[owner][weight]');
  }

}
