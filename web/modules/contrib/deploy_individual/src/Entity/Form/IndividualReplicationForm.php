<?php

namespace Drupal\deploy_individual\Entity\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\ReplicatorInterface;
use Drupal\workspace\WorkspacePointerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Replication edit forms.
 *
 * Based on the Drupal\deploy\Entity\Form\ReplicationForm.
 */
class IndividualReplicationForm extends ContentEntityForm {

  /**
   * The conflict tracker.
   *
   * @var \Drupal\multiversion\Workspace\ConflictTrackerInterface
   */
  protected $conflictTracker;

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorInterface
   */
  protected $replicatorManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The source workspace pointer.
   *
   * @var WorkspacePointerInterface
   */
  protected $source = NULL;

  /**
   * The target workspace pointer.
   *
   * @var WorkspacePointerInterface
   */
  protected $target = NULL;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\multiversion\Workspace\ConflictTrackerInterface $conflict_tracker
   *   The conflict tracker.
   * @param \Drupal\workspace\ReplicatorInterface $replicator_manager
   *   The replicator manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
      EntityManagerInterface $entity_manager,
      ConflictTrackerInterface $conflict_tracker,
      ReplicatorInterface $replicator_manager,
      RendererInterface $renderer,
      WorkspaceManagerInterface $workspace_manager,
      EntityTypeManagerInterface $entity_type_manager,
      DateFormatterInterface $date_formatter
    ) {
    parent::__construct($entity_manager);
    $this->conflictTracker = $conflict_tracker;
    $this->replicatorManager = $replicator_manager;
    $this->renderer = $renderer;
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('workspace.conflict_tracker'),
      $container->get('workspace.replicator_manager'),
      $container->get('renderer'),
      $container->get('workspace.manager'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Title of the form page for the individual deployment.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A TranslatableMarkup object.
   */
  public function addTitle() {
    $this->setEntity(Replication::create());
    if (!$this->getDefaultSource() || !$this->getDefaultTarget()) {
      return $this->t('Error');
    }
    return $this->t('Deploy @source to @target', [
      '@source' => $this->getDefaultSource()->label(),
      '@target' => $this->getDefaultTarget()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $js = isset($input['_drupal_ajax']) ? TRUE : FALSE;

    $form = parent::buildForm($form, $form_state);

    if (!$this->getDefaultSource() || !$this->getDefaultTarget()) {
      $message = $this->t(
        'Source and target must be set, make sure your current workspace has an upstream. Go to <a href=":path">this page</a> to edit your workspaces.',
        [':path' => Url::fromRoute('entity.workspace.collection')->toString()]
      );
      if ($js) {
        return ['#markup' => $message];
      }
      drupal_set_message($message, 'error');
      return [];
    }

    // Allow the user to not abort on conflicts.
    $source_workspace = $this->getDefaultSource()->getWorkspace();
    $target_workspace = $this->getDefaultTarget()->getWorkspace();
    $conflicts = $this->conflictTracker
      ->useWorkspace($source_workspace)
      ->getAll();
    if ($conflicts) {
      $form['message'] = $this->generateMessageRenderArray('error', $this->t(
        'There are <a href=":link">@count conflict(s) with the :target workspace</a>. Pushing changes to :target may result in unexpected behavior or data loss, and cannot be undone. Please proceed with caution.',
        [
          '@count' => count($conflicts),
          ':link' => Url::fromRoute('entity.workspace.conflicts', ['workspace' => $source_workspace->id()])->toString(),
          ':target' => $target_workspace->label(),
        ]
      ));
      $form['is_aborted_on_conflict'] = [
        '#type' => 'radios',
        '#title' => $this->t('Abort if there are conflicts?'),
        '#default_value' => 'true',
        '#options' => [
          'true' => $this->t('Yes, if conflicts are found do not replicate to upstream.'),
          'false' => $this->t('No, go ahead and push any conflicts to the upstream.'),
        ],
        '#weight' => 0,
      ];
    }
    else {
      $form['message'] = $this->generateMessageRenderArray('status', 'There are no conflicts.');
    }

    $form['source']['widget']['#default_value'] = [$this->getDefaultSource()->id()];

    if (empty($this->entity->get('target')->target_id) && $this->getDefaultTarget()) {
      $form['target']['widget']['#default_value'] = [$this->getDefaultTarget()->id()];
    }

    if (!$form['source']['#access'] && !$form['target']['#access']) {
      $form['actions']['submit']['#value'] = $this->t('Deploy to @target', ['@target' => $this->getDefaultTarget()->label()]);
    }
    else {
      $form['actions']['submit']['#value'] = $this->t('Deploy');
    }

    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'deploy'],
      'event' => 'mousedown',
      'prevent' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => 'Deploying',
      ],
    ];

    // The above code is almost the same code as
    // \Drupal\deploy\Entity\Form\ReplicationForm::buildForm().
    // Set default value for title.
    $form['name']['widget'][0]['value']['#default_value'] = $this->t(
      '%date to %target',
      array(
        '%date' => $this->dateFormatter->format(REQUEST_TIME, 'short'),
        '%target' => !is_null($target_workspace) ? $target_workspace->label() : $this->target->getName(),
      )
    );

    // Table to select entities.
    $header = [
      'label' => $this->t('Label'),
    ];

    $options = array();
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple();
    foreach ($nodes as $node) {
      $options[$node->uuid()] = array(
        'label' => $node->label(),
      );
    }

    $form['entities'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No entities found'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Ensure at least one entity is selected.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);

    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);
    if (empty($selected_entities)) {
      $form_state->setErrorByName('entities', $this->t('You must select at least one entity.'));
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Pass the abort flag to the ReplicationManager using runtime-only state,
    // i.e. a static.
    // @see \Drupal\workspace\ReplicatorManager
    // @see \Drupal\workspace\Entity\Form\WorkspaceForm
    $is_aborted_on_conflict = !$form_state->hasValue('is_aborted_on_conflict') || $form_state->getValue('is_aborted_on_conflict') === 'true';
    drupal_static('workspace_is_aborted_on_conflict', $is_aborted_on_conflict);

    parent::save($form, $form_state);

    $input = $form_state->getUserInput();
    $js = isset($input['_drupal_ajax']) ? TRUE : FALSE;

    $task = new ReplicationTask();

    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);
    if (!empty($selected_entities)) {
      $task->setParameter('uuids', array_keys($selected_entities));
    }
    else {
      // In case the validation is passed and no entities is selected. Put a
      // fake uuid to avoid to deploy all content.
      $task->setParameter('uuids', array('none'));
    }

    try {
      $response = $this->replicatorManager->replicate(
        $this->entity->get('source')->entity,
        $this->entity->get('target')->entity,
        $task
      );

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
        $this->entity->set('replicated', REQUEST_TIME)->save();
        drupal_set_message($this->t('Successful deployment.'));
      }
      else {
        drupal_set_message($this->t('Deployment error. Check recent log messages for more details.'), 'error');
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Deploy individual', $e);
      drupal_set_message($e->getMessage(), 'error');
    }

    if (!$js) {
      $form_state->setRedirect('entity.replication.collection');
    }
  }

  /**
   * Method called via ajax submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AjaxResponse.
   */
  public function deploy() {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.region-highlighted', $this->renderer->renderRoot($status_messages)));
    return $response;
  }

  /**
   * Helper function to return the default source.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\workspace\WorkspacePointerInterface|mixed
   *   A WorkspacePointer to the source.
   */
  protected function getDefaultSource() {
    if (!empty($this->source)) {
      return $this->source;
    }

    if (!empty($this->entity->get('source')) && ($this->entity->get('source')->entity instanceof WorkspacePointerInterface)) {
      return $this->source = $this->entity->get('source')->entity;
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

    if (!empty($this->entity->get('target')) && ($this->entity->get('target')->entity instanceof WorkspacePointerInterface)) {
      return $this->target = $this->entity->get('target')->entity;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    return $this->target = $workspace->get('upstream')->entity;
  }

  /**
   * Generate a message render array with the given text.
   *
   * @param string $type
   *   The type of message: status, warning, or error.
   * @param string $message
   *   The message to create with.
   *
   * @return array
   *   The render array for a status message.
   *
   * @see \Drupal\Core\Render\Element\StatusMessages
   */
  protected function generateMessageRenderArray($type, $message) {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        $type => [Markup::create($message)],
      ],
    ];
  }

}
