<?php

namespace Drupal\activity\Form;

use Drupal\activity\QueryActivity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Configure activities form.
 */
class ConfigureActivityForm extends MultiStepFormBase {

  /**
   * The tree builder.
   *
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $treeBuilder;

  /**
   * The token entity mapper.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $entityMapper;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Activity service.
   *
   * @var \Drupal\activity\QueryActivity
   */
  protected $activityService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event name.
   *
   * @var string
   */
  protected $label;

  /**
   * The event when the action should happen.
   *
   * @var string
   */
  protected $hook;

  /**
   * The event id.
   *
   * @var string
   */
  protected $eventId;

  /**
   * ConfigureActivityForm constructor.
   *
   * @param \Drupal\token\TreeBuilderInterface $tree_builder
   *   The tree builder.
   * @param \Drupal\token\TokenEntityMapperInterface $entity_mapper
   *   The entity mapper.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Session\SessionManagerInterface $sessionManager
   *   The session manager.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory.
   * @param \Drupal\activity\QueryActivity $activityService
   *   The activity service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(TreeBuilderInterface $tree_builder,
      TokenEntityMapperInterface $entity_mapper,
      Connection $database,
      AccountInterface $currentUser,
      SessionManagerInterface $sessionManager,
      PrivateTempStoreFactory $tempStoreFactory,
      QueryActivity $activityService,
      EntityTypeManagerInterface $entityTypeManager,
      ModuleHandlerInterface $moduleHandler) {
    parent::__construct($currentUser, $sessionManager, $tempStoreFactory);
    $this->treeBuilder = $tree_builder;
    $this->entityMapper = $entity_mapper;
    $this->database = $database;
    $this->activityService = $activityService;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->label = $this->store->get('label');
    $this->hook = $this->store->get('hook');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token.tree_builder'),
      $container->get('token.entity_mapper'),
      $container->get('database'),
      $container->get('current_user'),
      $container->get('session_manager'),
      $container->get('user.private_tempstore'),
      $container->get('query_activity'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'configure_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default value for Activity window field.
    $windowDefault = '';
    // Default value for roles field.
    $rolesDefault = '';
    // Default value for content types field.
    $typesDefault = '';
    // Default value for message field.
    $messageDefault = '';

    $form = parent::buildForm($form, $form_state);
    $current_path = \Drupal::service('path.current')->getPath();
    $result = \Drupal::service('path.alias_manager')
      ->getAliasByPath($current_path);
    $path_args = explode('/', $result);
    // The event id.
    $this->eventId = $path_args[4];
    // If event exists, get label form database.
    if ($this->eventId != 'new') {
      $query = $this->activityService->getActivityEventField($this->eventId, 'label');
      $this->label = $query[0]->label;
      $query = $this->activityService->getActivityEventField($this->eventId, 'hook');
      $this->hook = $query[0]->hook;
    }
    // Get fields values if they exist.
    $messageJson = $this->activityService->getActivityEventField($this->eventId, 'message');
    if (!empty($messageJson)) {
      $activityMessage = json_decode($messageJson[0]->message);
      $windowDefault = $activityMessage->window;
      $rolesDefault = $activityMessage->roles;
      $typesDefault = $activityMessage->types;
      $messageDefault = $activityMessage->message;
    }
    // Event name.
    $form['activity_label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->label,
      '#required' => TRUE,
      '#size' => 30,
      '#attributes' => [
        'class' => [
          'activity_label',
        ],
      ],
    ];
    // Options for activity window.
    $timeIntervals = range(0, 7200, 300);
    $options = $this->buildOptions($timeIntervals);
    $options[0] = 'Unlimited';
    $form['activity_window'] = [
      '#type' => 'select',
      '#title' => t('Activity Window'),
      '#description' => t('Prevent repeat Activity from the same user for the same entity within this interval'),
      '#options' => $options,
      '#default_value' => $windowDefault == '' ? $this->options['window'] : $windowDefault,
    ];

    // Add roles or content types options based on hook.
    // Do not need content types when need to update user for example.
    if (strpos($this->hook, 'user') !== FALSE) {
      $roles = Role::loadMultiple();
      $roleOptions = [];
      foreach ($roles as $role => $value) {
        $roleOptions[$role] = $role;
      }
      $form['activity_roles'] = [
        '#type' => 'checkboxes',
        '#title' => t('Roles'),
        '#default_value' => $rolesDefault,
        '#options' => $roleOptions,
      ];
    }
    else {
      $bundles = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple();
      $contentTypes = array_keys($bundles);
      $types = array_combine($contentTypes, $contentTypes);
      $form['activity_node_types'] = [
        '#type' => 'checkboxes',
        '#title' => t('Allowed Node Types'),
        '#default_value' => $typesDefault,
        '#options' => $types,
      ];
    }

    // Message that keeps all the options.
    $form['activity_message'] = [
      '#type' => 'textarea',
      '#title' => t('Public Message'),
      '#description' => t('Message displayed to everyone who is not part of this Activity.'),
      '#default_value' => $messageDefault,
    ];
    // The token.module provides the UI for the tokens when module enabled.
    $moduleHandler = $this->moduleHandler;
    if ($moduleHandler->moduleExists('token')) {
      // Get tokens options.
      $token_tree = $this->treeBuilder->buildAllRenderable([
        'click_insert' => TRUE,
        'show_restricted' => TRUE,
        'show_nested' => FALSE,
      ]);
      // Interest only on these types.
      $tokenFor = [
        'current-date' => 'current-date',
        'current-page' => 'current-page',
        'current-user' => 'current-user',
        'node' => 'node',
        'user' => 'user',
        'random' => 'random',
        'site' => 'site',
      ];
      if (strpos($this->hook, 'user') !== FALSE) {
        unset($tokenFor['node']);
      }
      elseif (strpos($this->hook, 'comment') !== FALSE) {
        $tokenFor['comment'] = 'comment';
      }

      foreach ($token_tree['#token_tree'] as $key => $value) {
        if (!in_array($key, $tokenFor)) {
          unset($token_tree['#token_tree'][$key]);
        }
      }
      $form['token_help'] = [
        '#type' => 'markup',
        '#markup' => \Drupal::service('renderer')->render($token_tree),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    return $form;
  }

  /**
   * Window interval.
   */
  public function buildOptions(array $timeIntervals) {
    $callback = [$this, 'callbackOptions'];
    return array_combine($timeIntervals, array_map($callback, $timeIntervals));
  }

  /**
   * Callback for buildOptions function.
   */
  public function callbackOptions($value, $langcode, $granularity = 2) {
    return \Drupal::service('date.formatter')->formatInterval($value, $granularity, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If the event is not new, get hook from database.
    if ($this->hook == NULL) {
      $query = $this->activityService->getActivityEventField($this->eventId, 'hook');
      $this->hook = $query[0]->hook;
    }
    // Get options to insert them in database.
    $userId = \Drupal::currentUser()->id();
    $this->label = $form_state->getValue('activity_label');
    $windowOption = $form_state->getValue('activity_window');
    $roleOptions = $form_state->getValue('activity_roles') == NULL ? NULL : array_filter(array_values($form_state->getValue('activity_roles')));
    $contentTypes = $form_state->getValue('activity_node_types') == NULL ? NULL : array_filter(array_values($form_state->getValue('activity_node_types')));
    $message = $form_state->getValue('activity_message');
    $messageArray = [
      'window' => $windowOption,
      'roles' => $roleOptions,
      'types' => $contentTypes,
      'message' => $message,
    ];
    // Update row based on event id.
    if ($this->eventId != 'new') {
      $this->database->update('activity_events')
        ->fields([
          'label' => $this->label,
          'hook' => $this->hook,
          'userId' => $userId,
          'message' => json_encode($messageArray),
        ])
        ->condition('event_id', $this->eventId)
        ->execute();
    }
    // Insert event base on event id.
    else {
      $this->database->insert('activity_events')
        ->fields([
          'label' => $this->label,
          'hook' => $this->hook,
          'userId' => $userId,
          'created' => \Drupal::time()->getCurrentTime(),
          'message' => json_encode($messageArray),
        ])
        ->execute();
    }

    // Set label and hook to be null.
    $this->deleteStore();
    $url = Url::fromUri('internal:/admin/activity');
    $form_state->setRedirectUrl($url);
  }

}
