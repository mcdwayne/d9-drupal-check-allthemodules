<?php

namespace Drupal\workflow_task\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\workflow_task\Plugin\Field\TaskStateFieldItemList;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines the Workflow task entity.
 *
 * @ingroup workflow_task
 *
 * @ContentEntityType(
 *   id = "workflow_task",
 *   label = @Translation("Workflow task"),
 *   bundle_label = @Translation("Workflow task type"),
 *   handlers = {
 *     "storage" = "Drupal\workflow_task\WorkflowTaskStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\workflow_task\WorkflowTaskListBuilder",
 *     "views_data" = "Drupal\workflow_task\Entity\WorkflowTaskViewsData", *
 *     "form" = {
 *       "default" = "Drupal\workflow_task\Form\WorkflowTaskForm",
 *       "add" = "Drupal\workflow_task\Form\WorkflowTaskForm",
 *       "edit" = "Drupal\workflow_task\Form\WorkflowTaskForm",
 *       "delete" = "Drupal\workflow_task\Form\WorkflowTaskDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\workflow_task\WorkflowTaskHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\workflow_task\WorkflowTaskAccessControlHandler",
 *   },
 *   base_table = "workflow_task",
 *   revision_table = "workflow_task_revision",
 *   revision_data_table = "workflow_task_field_revision",
 *   admin_permission = "administer workflow task entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/workflow_task/{workflow_task}",
 *     "add-page" = "/admin/structure/workflow_task/add",
 *     "add-form" = "/admin/structure/workflow_task/add/{workflow_task_type}",
 *     "edit-form" = "/admin/structure/workflow_task/{workflow_task}/edit",
 *     "delete-form" = "/admin/structure/workflow_task/{workflow_task}/delete",
 *     "version-history" = "/admin/structure/workflow_task/{workflow_task}/revisions",
 *     "revision" = "/admin/structure/workflow_task/{workflow_task}/revisions/{workflow_task_revision}/view",
 *     "revision_revert" = "/admin/structure/workflow_task/{workflow_task}/revisions/{workflow_task_revision}/revert",
 *     "revision_delete" = "/admin/structure/workflow_task/{workflow_task}/revisions/{workflow_task_revision}/delete",
 *     "collection" = "/admin/structure/workflow_task",
 *   },
 *   bundle_entity_type = "workflow_task_type",
 *   field_ui_base_route = "entity.workflow_task_type.edit_form"
 * )
 */
class WorkflowTask extends RevisionableContentEntityBase implements WorkflowTaskInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    /** @var \Drupal\workflow_task\Entity\WorkflowTaskTypeInterface $workflowTaskType */
    $workflowTaskType = \Drupal::service('entity_type.manager')->getStorage('workflow_task_type')->load($values['type']);

    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'workflow' => $workflowTaskType->getDefaultWorkflowId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if($this instanceof RevisionableInterface) {
      if ($rel === 'revision_revert') {
        $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
      }
      elseif ($rel === 'revision_delete') {
        $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
      }
    }


    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the workflow_task owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->get('workflow')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflowId) {
    $this->set('workflow', $workflowId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow() {
    return $this->get('workflow')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflow(WorkflowInterface $workflow) {
    $this->set('workflow', $workflow);
    return $this;
  }

  public function getStateId() {
    $bla = $this->get('state');
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateId($stateId) {
    $this->set('state', $stateId);
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    $workflow = $this->getWorkflow();
    $workflow->getTypePlugin()
      ->getState($this->getStateId());
  }

  /**
   * {@inheritdoc}
   */
  public function setState(StateInterface $state) {
    $this->setStateId($state->id());
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Workflow task entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Workflow task entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['workflow'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Workflow'))
      ->setDescription(t('The workflow to use with this task.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'workflow')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setDescription(t('The state of the task in the workflow.'))
      ->setClass(TaskStateFieldItemList::class)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'task_state_default',
        'weight' => 100,
        'settings' => [],
      ])
      ->addConstraint('TaskState', [])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
