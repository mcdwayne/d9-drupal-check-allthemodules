<?php

namespace Drupal\deploy_individual\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\workspace\ReplicatorInterface;
use Drupal\workspace\WorkspacePointerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Replication edit forms.
 *
 * Based on the Drupal\deploy\Entity\Form\ReplicationForm.
 */
class IndividualPullForm extends FormBase {

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorInterface
   */
  protected $replicatorManager;

  /**
   * The workspace manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The source workspace pointer.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $source = NULL;

  /**
   * The target workspace pointer.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $target = NULL;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\workspace\ReplicatorInterface $replicator_manager
   *   The replicator manager.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
      ReplicatorInterface $replicator_manager,
      WorkspaceManagerInterface $workspace_manager,
      EntityTypeManagerInterface $entity_type_manager
    ) {
    $this->replicatorManager = $replicator_manager;
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.replicator_manager'),
      $container->get('workspace.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deploy_individual_pull_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $source = $this->getDefaultSource();
    $target = $this->getDefaultTarget();

    if (!$source || !$target) {
      $message = $this->t(
        'Source and target must be set, make sure your current workspace has an upstream. Go to <a href=":path">this page</a> to edit your workspaces.',
        [':path' => Url::fromRoute('entity.workspace.collection')->toString()]
      );
      drupal_set_message($message, 'error');
      return [];
    }

    $form['source'] = array(
      '#type' => 'hidden',
      '#value' => $source->id(),
    );
    $form['target'] = array(
      '#type' => 'hidden',
      '#value' => $target->id(),
    );

    // Table to select entities.
    $header = [
      'label' => $this->t('Label'),
      'type' => $this->t('Type'),
      'bundle' => $this->t('Bundle'),
    ];

    $options = array();
    $entities = $this->replicatorManager->getDiffEntities(
      $target,
      $source
    );

    foreach ($entities as $entity) {
      $options[$entity->uuid()] = array(
        'label' => $entity->label(),
        'type' => $entity->getEntityType()->getLabel(),
        'bundle' => $entity->bundle(),
      );
    }

    $form['entities'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No entities to be pulled have been found.'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Pull from @target', ['@target' => $target->label()]),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Ensure at least one entity is selected.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);
    if (empty($selected_entities)) {
      $form_state->setErrorByName('entities', $this->t('You must select at least one entity.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);

    $task = $this->prepareTask($this->getDefaultTarget(), $selected_entities);

    try {
      $response = $this->replicatorManager->update(
        $this->getDefaultTarget(),
        $this->getDefaultSource(),
        $task
      );

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
        drupal_set_message($this->t('Successful update.'));
      }
      else {
        drupal_set_message($this->t('Update error. Check recent log messages for more details.'), 'error');
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Deploy individual', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Helper function to return the default source.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface|mixed
   *   A WorkspacePointer to the source.
   */
  protected function getDefaultSource() {
    if (!empty($this->source)) {
      return $this->source;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    $workspace_pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    return $this->source = reset($workspace_pointers);
  }

  /**
   * Helper function to return the default target.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   A WorkspacePointer to the target.
   */
  protected function getDefaultTarget() {
    if (!empty($this->target)) {
      return $this->target;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    return $this->target = $workspace->get('upstream')->entity;
  }

  /**
   * Helper function to prepare a replication task.
   *
   * If it is a remote workspace (supposed using the relaxed module), need to
   * use a different task object.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $upstream
   *   The target workspace from which to pull content from.
   * @param array $selected_entities
   *   An array of selected entities.
   *
   * @return \Relaxed\Replicator\ReplicationTask|\Drupal\replication\ReplicationTask\ReplicationTask
   *   A replication task.
   */
  protected function prepareTask(WorkspacePointerInterface $upstream, array $selected_entities) {
    // Remote workspace.
    if (!empty($upstream->get('remote_database')->value)) {
      $task = new \Relaxed\Replicator\ReplicationTask();

      if (!empty($selected_entities)) {
        $task->setDocIds(array_keys($selected_entities));
      }
      else {
        // In case the validation is passed and no entities is selected. Put a
        // fake uuid to avoid to deploy all content.
        $task->setDocIds(array('none'));
      }
    }
    else {
      $task = new ReplicationTask();

      if (!empty($selected_entities)) {
        $task->setParameter('uuids', array_keys($selected_entities));
      }
      else {
        // In case the validation is passed and no entities is selected. Put a
        // fake uuid to avoid to deploy all content.
        $task->setParameter('uuids', array('none'));
      }
    }

    return $task;
  }

}
