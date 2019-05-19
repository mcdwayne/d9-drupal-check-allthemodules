<?php

namespace Drupal\user_request\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_request\Entity\ResponseType;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for request types.
 */
class RequestTypeForm extends BundleEntityFormBase {

  /**
   * Workflow plugin manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */

  /**
   * Constructor.
   *
   * @param \Drupal\state_machine\WorkflowManagerInterface
   *   Workflow plugin manager.
   * @para \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler.
   */
  public function __construct(WorkflowManagerInterface $workflow_manager, ModuleHandlerInterface $module_handler) {
    $this->workflowManager = $workflow_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.workflow'), $container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\user_request\Entity\RequestType::load',
      ],
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
    ];

    // Response type option.
    $response_types = ResponseType::loadMultiple();
    $response_type_options = array_map(function ($e) {
      return $e->label();
    }, $response_types);
    asort($response_type_options);
    $form['response_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Response type'),
      '#description' => $this->t('The response type allowed for this request type.'),
      '#options' => $response_type_options,
      '#required' => TRUE,
      '#default_value' => $this->entity->getResponseType(),
    ];

    // Vertical tabs.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['workflow_tab'] = [
      '#type' => 'details',
      '#title' => $this->t('Workflow'),
      '#group' => 'tabs',
    ];
    $form['messages_tab'] = [
      '#type' => 'details',
      '#title' => $this->t('Messages'),
      '#group' => 'tabs',
    ];

    // Workflow option.
    $workflow_labels = $this->workflowManager->getGroupedLabels('user_request');
    $workflow_options = [];
    foreach ($workflow_labels as $workflows) {
      $workflow_options += $workflows;
    }
    asort($workflow_options);
    $form['workflow_tab']['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#description' => $this->t('Workflows are provided by modules to define allowed states and transitions.'),
      '#options' => $workflow_options,
      '#required' => TRUE,
      '#default_value' => $this->entity->getWorkflow(),
      '#ajax' => [
        'callback' => [$this, 'onWorkflowSelected'],
        'wrapper' => 'response-transitions-wrapper',
      ],
    ];

    // Response transitions.
    $form['workflow_tab']['response_transitions_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'response-transitions-wrapper',
        'class' => ['hidden'],
      ],
    ];
    $form['workflow_tab']['response_transitions_wrapper']['response_transitions'] = [
      '#type' => 'select',
      '#title' => $this->t('Response transitions'),
      '#description' => $this->t('These transitions can only be performed when responding the request.'),
      '#options' => [],
      '#default_value' => $this->entity->getResponseTransitions(),
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];
    $form['workflow_tab']['response_transitions_wrapper']['deleted_response_transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Deleted response transition'),
      '#description' => $this->t('When this transition is performed, the response is deleted. Reciprocally, this transition is performed when the response is deleted.'),
      '#options' => [],
      '#default_value' => $this->entity->getDeletedResponseTransition(),
    ];

    // Fills the transition options if a workflow was selected.
    $transition_options = [];
    $workflow_id = $form_state->getValue('workflow');
    if (!$workflow_id) {
      // Form was still not submitted.
      $workflow_id = $form['workflow_tab']['workflow']['#default_value'];
    }
    if ($workflow_id) {
      // Gets transition options for selected workflow.
      $workflow = $this->workflowManager->createInstance($workflow_id);
      $transitions = $workflow->getTransitions();
      $transition_options = [];
      foreach ($transitions as $transition_id => $transition) {
        $transition_options[$transition_id] = $transition->getLabel();
      }
      asort($transition_options);

      // Fills the options in the form elements and make them visible.
      $wrapper = &$form['workflow_tab']['response_transitions_wrapper'];
      $wrapper['response_transitions']['#options'] = $transition_options;
      $wrapper['deleted_response_transition']['#options'] = ['' => $this->t('- None -')] + $transition_options;
      $wrapper['#attributes']['class'] = [];
    }

    // Adds the message tab if the Sender module is enabled.
    if ($this->moduleHandler->moduleExists('sender')) {
      $messages = $this->entity->getMessages();
      $form['messages_tab']['messages'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $form['messages_tab']['messages']['request_sent'] = [
        '#type' => 'sender_message_select',
        '#title' => $this->t('Request sent'),
        '#description' => $this->t('This message is sent to the user who sent the request.'),
        '#message_group' => 'user_request',
        '#default_value' => $messages['request_sent'] ?: NULL,
      ];
      $form['messages_tab']['messages']['request_received'] = [
        '#type' => 'sender_message_select',
        '#title' => $this->t('Request received'),
        '#description' => $this->t('This message is sent to the user who received the request.'),
        '#message_group' => 'user_request',
        '#default_value' => $messages['request_received'] ?: NULL,
      ];

      // Messages for transitions.
      $form['messages_tab']['messages']['transitions'] = [
        '#type' => 'details',
        '#title' => $this->t('Transitions'),
        '#description' => $this->t("These messages are sent to the request's sender and recipients. Each message corresponds to a workflow transition."),
        '#open' => TRUE,
        '#attributes' => [
          'id' => 'messages-transitions',
        ],
      ];
      foreach ($transition_options as $transition_id => $transition_label) {
        $form['messages_tab']['messages']['transitions'][$transition_id] = [
          '#type' => 'sender_message_select',
          '#title' => $transition_label,
          '#message_group' => 'user_request',
          '#default_value' => $messages['transitions'][$transition_id] ?: NULL,
        ];
      }
    }
    else {
      $form['messages_tab']['instructions'] = [
        '#markup' => $this->t('Enable the <a href="https://www.drupal.org/project/sender">Sender</a> module to send messages when requests are created or updated.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    // Removes the #after_build callback because it causes "illegal choice" 
    // errors whe the AJAX is triggered.
    unset($form['#after_build']);

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validates the "delete response" transition.
    if ($transition_id = $form_state->getValue('deleted_response_transition')) {
      // The "delete response" transition cannot be used to respond the 
      // request.
      $response_transitions = $form_state->getValue('response_transitions');
      if ($response_transitions && in_array($transition_id, $response_transitions)) {
        $message = $this->t('The response cannot be deleted when a response transition is performed.');
        $form_state->setErrorByName('deleted_response_transition', $message);
      }
    }
  }

  /**
   * AJAX callback to return a populate list of transitions for selected
   * workflow.
   *
   * @param array &$form
   *   A reference to the form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function onWorkflowSelected(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#response-transitions-wrapper', $form['workflow_tab']['response_transitions_wrapper']));
    $response->addCommand(new ReplaceCommand('#messages-transitions', $form['messages_tab']['messages']['transitions']));
    return $response;
  }

}
