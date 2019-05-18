<?php

namespace Drupal\access_filter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides filter config entity list builder.
 */
class AccessFilterListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'filters';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_filter_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['conditions'] = $this->t('Conditions');
    $header['rules'] = $this->t('Rules');
    $header['response'] = $this->t('Response');
    if (!empty($this->weightKey)) {
      $header['weight'] = $this->t('Weight');
    }
    $header['operations'] = $this->t('Operations');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\access_filter\Entity\Filter $entity */
    $entity->parse();

    if (!$entity->status()) {
      $row['#attributes']['class'] = ['disabled', 'draggable'];
    }

    $row['id']['data'] = ['#plain_text' => $entity->id()];
    $row['name']['data'] = ['#plain_text' => $entity->label()];

    $conditions = [];
    foreach ($entity->parsedConditions as $condition) {
      $negated_marker = $condition->isNegated() ? '<span class="negated">!</span>' : '';
      $conditions[] = ['#markup' => $negated_marker . Html::escape($condition->getPluginId()) . ': ' . $condition->summary()];
    }
    $row['conditions']['data'] = [
      '#theme' => 'item_list',
      '#items' => $conditions,
    ];

    $rules = [];
    foreach ($entity->parsedRules as $rule) {
      $rules[] = ['#markup' => Html::escape($condition->getPluginId()) . ': ' . $rule->summary()];
    }
    $row['rules']['data'] = [
      '#theme' => 'item_list',
      '#items' => $rules,
    ];

    $row['response']['data'] = ['#plain_text' => $entity->parsedResponse['code']];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $build['table']['#empty'] = t('No filters available.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'access_filter/common';
    $form['filters']['#attributes'] = ['id' => 'filters'];
    $form['actions']['submit']['#value'] = $this->t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
