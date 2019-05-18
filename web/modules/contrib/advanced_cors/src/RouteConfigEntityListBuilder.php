<?php

namespace Drupal\advanced_cors;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of Route configuration entities.
 */
class RouteConfigEntityListBuilder extends DraggableListBuilder {

  protected $patterns;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->patterns = \Drupal::service('advanced_cors.patterns_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_cors_overview_route_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['status'] = $this->t('Status');
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Machine name');
    $header['origins'] = $this->t('Allowed Origins');
    $header['methods'] = $this->t('Allowed methods');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['status'] = [
      '#markup' => $entity->status() ? $this->t('On') : $this->t('Off'),
    ];
    $row['name'] = [
      '#markup' => $entity->label(),
    ];
    $row['id'] = [
      '#markup' => $entity->id(),
    ];
    $row['origins'] = [
      '#markup' => $entity->get('allowed_origins'),
    ];
    $row['methods'] = [
      '#markup' => $entity->get('allowed_methods'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->patterns->resetCache();
  }

}
