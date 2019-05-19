<?php

namespace Drupal\workflow_notifications\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\WorkflowTypeAttributeTrait;
use Drupal\workflow_notifications\Entity\WorkflowNotification;

/**
 * Class WorkflowNotificationForm
 */
class WorkflowNotificationForm extends EntityForm {
  /*
   * Add variables and get/set methods for Workflow property.
   */
  use WorkflowTypeAttributeTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var WorkflowNotification $workflow_notification */
    $workflow_notification = $this->entity;

    $workflow = workflow_url_get_workflow();
    $this->setWorkflow($workflow);

    $role_options = workflow_get_user_role_names('');
    unset($role_options['anonymous']);

    $state_options = ['' => 'Any State'];
    $state_options += workflow_get_workflow_state_names($workflow->id(), FALSE);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $workflow_notification->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $workflow_notification->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$workflow_notification->isNew(),
    ];
    $form['wid'] = [
      '#type' => 'hidden',
      '#default_value' => $this->getWorkflowId(),
    ];
    $form['from_sid'] = [
      '#type' => 'select',
      '#title' => t('From state'),
      '#options' => $state_options,
      '#default_value' => $workflow_notification->from_sid,
    ];
    $form['to_sid'] = [
      '#type' => 'select',
      '#title' => t('To state'),
      '#options' => $state_options,
      '#default_value' => $workflow_notification->to_sid,
    ];
    $form['when_to_trigger'] = [
      '#type' => 'radios',
      '#title' => t('When to trigger'),
      '#options' => ['on_state_change' => t('On State change'), 'before_state_change' => t('Before State change')],
      '#default_value' => $workflow_notification->when_to_trigger,
      '#description' => t('Determine when the message nust be sent:
        a) directoy upon a state change, or
        b) some days before a state change (using scheduled transitions).'),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['when-to-trigger'],
      ],
    ];
    $form['days'] = [
      '#type' => 'number',
      '#title' => t('Days'),
      '#min' => 0,
      '#default_value' => $workflow_notification->days,
      '#description' => t('Enter the number of days before a transition is scheduled, a message must be sent.'),
      '#attributes' => [
        'class' => ['time'],
      ],
      '#states' => [
        'invisible' => [
          'input[name="when_to_trigger"]' => ['value' => 'on_state_change'],
        ],
      ],
    ];
    $form['mail_to'] = [
      '#type' => 'fieldset',
      '#title' => t('Mail To'),
      '#collapsible' => TRUE,
    ];
    $form['mail_to']['roles'] = [
      '#type' => 'checkboxes',
      '#options' => $role_options,
      '#title' => t('Roles'),
      '#default_value' => $workflow_notification->roles,
      '#description' => t('Check each role that must be informed.'),
    ];
    // @todo: add validation for email adresses.
    $form['mail_to']['mail_ids'] = [
      '#type' => 'textarea',
      '#title' => t('Email adresses'),
      '#default_value' => isset($workflow_notification->mail_ids)? $workflow_notification->mail_ids : "",
      '#description' => t('Enter a valid Email address, one per line.'),
    ];
    $form['template'] = [
      '#type' => 'fieldset',
      '#title' => t('Template'),
      '#collapsible' => TRUE,
    ];
    $form['template']['subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => isset($workflow_notification->subject)? $workflow_notification->subject : "",
      '#required' => TRUE,
    ];
    $form['template']['message'] = [
      '#type' => 'text_format',
      '#title' => t('Message'),
      '#default_value' => $workflow_notification->message['value'],
      '#format' => $workflow_notification->message['format'],
      '#required' => TRUE,
    ];
    $form['note'] = [
      '#type' => 'markup',
      '#markup' => t("<b>Note:</b> Token can be available in the listed fields - MailID, Subject, Message"),
    ];

    // Token support.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['tokens'] = [
        '#title' => $this->t('Tokens'),
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            'input[name="use_token"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['tokens']['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node', 'workflow_transition', 'workflow_scheduled_transition', 'term', 'site', 'paragraph', 'comment'],
        // '#token_types' => 'all'
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    if ($form_values['when_to_trigger'] == 'on_state_change') {
      $form_state->setValue('days', 0);
    }
    if(!empty($mailids = $form_values['mail_ids'])) {
      $values = "\r\n" . $mailids;
      $ids = array_filter(preg_split('/\r\n|[\r\n]/', $values));
      foreach($ids as $k => $id) {
        if(!\Drupal::service('email.validator')->isValid($id)) {
          $form_state->setErrorByName('mail_ids', t('The email address %mail is not valid.', ['%mail' => $id]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workflow_notification = $this->entity;
    $status = parent::save($form, $form_state);

    if ($status) {
      drupal_set_message($this->t('Saved the %label workflow notify.', [
        '%label' => $workflow_notification->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label workflow notify was not saved.', [
        '%label' => $workflow_notification->label(),
      ]));
    }

    $form_state->setRedirect('entity.workflow_notify.collection', ['workflow_type' => $this->getWorkflowId()]);
  }

  /**
   * Helper function for machine_name element.
   *
   * @param $id
   *   The given machine name.
   * @return bool
   *   Indicates if the machine name already exists.
   */
  public function exists($id) {
    $type = $this->entity->getEntityTypeId();
    return (bool) $this->entityTypeManager->getStorage($type)->load($id);
  }
}