<?php

namespace Drupal\drd;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Plugin\Action\BaseConfigurableInterface;
use Drupal\drd\Plugin\Action\BaseInterface;
use Drupal\drd\Plugin\Action\BaseEntityInterface;
use Drupal\system\ActionConfigEntityInterface;

/**
 * Class RemoteActions.
 *
 * @package Drupal\drd
 */
class RemoteActions implements RemoteActionsInterface {

  /**
   * Mode for action manager.
   *
   * @var string
   */
  private $mode;

  /**
   * Term for action manager.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private $term;

  /**
   * Number of actions found.
   *
   * @var int
   */
  private $count = 0;

  /**
   * Selected action entity.
   *
   * @var \Drupal\system\ActionConfigEntityInterface
   */
  private $action;

  /**
   * An array of actions that can be executed.
   *
   * @var \Drupal\system\ActionConfigEntityInterface[]
   */
  private $actions = [];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * List of entities for which actions should be executed.
   *
   * @var \Drupal\drd\Entity\BaseInterface[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->entityManager = \Drupal::entityTypeManager();
    $this->actionStorage = $this->entityManager->getStorage('action');
    $this->setMode('drd');
  }

  /**
   * {@inheritdoc}
   */
  public function setMode($mode) {
    $this->mode = $mode;
    $this->actions = array_filter($this->actionStorage->loadMultiple(), function (ActionConfigEntityInterface $action) use ($mode) {
      return $action->getType() == $mode && $action->getPlugin()->access(NULL);
    });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTerm($term) {
    $term = is_string($term) ?
      reset(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term])) :
      $term;
    if (empty($term)) {
      $this->actions = [];
    }
    else {
      $this->term = $term;
      $this->actions = array_filter($this->actionStorage->loadMultiple(), function (ActionConfigEntityInterface $action) use ($term) {
        $plugin = $action->getPlugin();
        return ($plugin instanceof BaseInterface) && $plugin->hasTerm($term) && $plugin->access(NULL);
      });
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionPlugins() {
    $result = [];
    foreach ($this->actions as $action) {
      $result[] = $action->getPlugin();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedAction() {
    return $this->action;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutedCount() {
    return $this->count;
  }

  /**
   * Get list of matching actions as a form API select list.
   *
   * @param array $options
   *   TODO.
   *
   * @return array
   *   List of actions for select form element.
   */
  protected function getBulkOptions(array $options) {
    $bulkOptions = [];
    // Filter the action list.
    foreach ($this->actions as $id => $action) {
      if (!empty($options['selected_actions'])) {
        $in_selected = in_array($id, $options['selected_actions']);
        if (($options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        elseif (($options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }

      $bulkOptions[$id] = $action->label();
    }

    asort($bulkOptions);
    return $bulkOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state, array $options = []) {
    $form['#type'] = 'container';
    $form['action'] = [
      '#type' => 'select',
      '#title' => t('Action'),
      '#options' => ['' => '-- ' . t('select')->render() . ' --'] + $this->getBulkOptions($options),
    ];
    $form['actions'] = [
      '#type' => 'container',
      '#weight' => 9,
      'submit' => [
        '#type' => 'submit',
        '#value' => t('Apply'),
      ],
    ];

    foreach ($form['action']['#options'] as $action_key => $action_label) {
      if (empty($action_key)) {
        continue;
      }
      $action = $this->actions[$action_key]->getPlugin();
      if ($action instanceof BaseConfigurableInterface) {
        $form[$action_key] = [
          '#type' => 'container',
          '#weight' => 8,
          '#states' => [
            'visible' => [
              '#edit-action' => ['value' => $action_key],
            ],
          ],
        ] + $action->buildConfigurationForm([], $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action = $this->actions[$form_state->getValue('action')]->getPlugin();
    if ($action instanceof BaseConfigurableInterface) {
      $action->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectedEntities($entities) {
    $this->entities = is_array($entities) ? $entities : [$entities];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    $this->action = $this->actions[$form_state->getValue('action')];
    /** @var \Drupal\drd\Plugin\Action\BaseEntityInterface $actionPlugin */
    $actionPlugin = $this->action->getPlugin();
    if ($actionPlugin instanceof BaseConfigurableInterface) {
      $actionPlugin->submitConfigurationForm($form, $form_state);
    }

    if ($actionPlugin instanceof BaseEntityInterface) {
      $permittedEntities = [];
      /** @var \Drupal\drd\Entity\BaseInterface $entity */
      foreach ($this->entities as $entity) {
        // Skip execution if the user did not have access.
        if (!$actionPlugin->access($entity)) {
          drupal_set_message(t('No access to execute %action on the @entity_type_label %entity_label.', [
            '%action' => $action->label(),
            '@entity_type_label' => $entity->getEntityType()->getLabel(),
            '%entity_label' => $entity->label(),
          ]), 'error');
          continue;
        }

        $this->count++;
        $permittedEntities[] = $entity;
      }
      \Drupal::service('queue.drd')->createItems($actionPlugin, $permittedEntities);
    }
    else {
      $this->count = 1;
      \Drupal::service('queue.drd')->createItem($actionPlugin);
    }

  }

}
