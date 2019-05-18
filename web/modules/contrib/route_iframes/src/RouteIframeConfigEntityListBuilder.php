<?php

namespace Drupal\route_iframes;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a listing of Route Iframe Configuration entities.
 */
class RouteIframeConfigEntityListBuilder extends DraggableListBuilder {

  /**
   * The mapping of scope machine names to the display.
   *
   * @var array
   */
  protected $scopeTypes;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    $this->scopeTypes = [
      'default' => $this->t('Default'),
      'bundle' => $this->t('Content Types'),
      'specific' => $this->t('List of IDs'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_iframes_config_list_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Route Iframe Configuration');
    $header['scope_type'] = $this->t('Scope Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['scope_type'] = ['#markup' => $this->scopeTypes[$entity->get('scope_type')]];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entities = $this->load();
    $this->groupEntitiesByScopeType();
    $form['description'] = [
      '#markup' => 'Route Iframe Configurations will be loaded in the same order as the entities on this page: List of (content) IDs, Content Types, and finally the Default. Starting with the list of content IDs, entities listed higher up on each table overrides items lower on the page.  ID specific configurations will override content type and default scopes, and content type scope will override default.  Within each scope, the top items (which would have a lower weight) override the lower items (which would have a higher weight).',
      '#weight' => -10,
    ];
    foreach ($this->entities as $scope_type => $entities) {
      $this->buildTable($form, $scope_type, $entities);
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds the form table for each scope.
   *
   * @param array $form
   *   The form to add the table to.
   * @param string $scope
   *   The configuration scope to display.
   * @param array $entities
   *   An array of route iframe configuration entities to display.
   */
  private function buildTable(array &$form, $scope, array $entities) {
    $weights = [
      'specific' => 0,
      'bundle' => 1,
      'default' => 2,
    ];
    $form[$scope] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#weight' => $weights[$scope],
      '#caption' => $this->scopeTypes[$scope],
    ];

    $delta = 10;
    // Change the delta of the weight field if have more than 20 entities.
    if (!empty($this->weightKey)) {
      $count = count($entities);
      if ($count > 20) {
        $delta = ceil($count / 2);
      }
    }
    foreach ($entities as $entity) {
      $row = $this->buildRow($entity);
      if (isset($row['label'])) {
        $row['label'] = ['#markup' => $row['label']];
      }
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form[$scope][$entity->id()] = $row;
    }
  }

  /**
   * Groups together route iframe configurations within the same scope.
   */
  private function groupEntitiesByScopeType() {
    if (empty($this->entities)) {
      return;
    }
    $group = [];
    /* @var $route_iframe \Drupal\route_iframes\Entity\RouteIframeConfigEntity */
    foreach ($this->entities as $id => $route_iframe) {
      $scope_type = $route_iframe->get('scope_type');
      $group[$scope_type][$id] = $route_iframe;
    }
    if (!empty($group)) {
      $this->entities = $group;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scopes = array_keys($this->scopeTypes);
    foreach ($scopes as $scope) {
      $entities = $form_state->getValue($scope);
      foreach ($entities as $id => $value) {
        if (isset($this->entities[$scope][$id]) && $this->entities[$scope][$id]->get($this->weightKey) != $value['weight']) {
          // Save entity only when its weight was changed.
          $this->entities[$scope][$id]->set($this->weightKey, $value['weight']);
          $this->entities[$scope][$id]->save();
        }
      }
    }
    drupal_set_message($this->t('The configurations have been reordered.'));
  }

}
