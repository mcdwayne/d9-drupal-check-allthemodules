<?php

namespace Drupal\workflow_sms_notification\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\Workflow;

/**
 * Provides the base form for workflow SMS notification add and edit forms.
 */
class WorkflowSmsNotificationForm extends EntityForm {


  /**
   * The workflow object.
   *
   * @var \Drupal\workflow\Entity\Workflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, NodeInterface $workflow = NULL, NodeInterface $notification = NULL) {
    if ($wid = $this->entity->getWorkflowId()) {
      $this->workflow = Workflow::load($wid);
    }
    else {
      $this->workflow = workflow_ui_url_get_workflow();
      $this->entity->setWorkflowId($this->workflow->id());
    }

    $states = $this->workflow->getStates(FALSE, TRUE);

    foreach ($states as $state) {
      $states_options[$state->id] = $state->label;
    }

    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $states_options,
      '#default_value' => $this->entity->getState(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'),
        'source' => ['state'],
      ],
    ];

    $default_message = $this->entity->getMessage() + [
      'value' => '',
      'format' => 'plain_text',
    ];

    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#description' => $this->t('SMS body, you may use tokens like [node:title] depending on the entity-type being updated.'),
      '#required' => TRUE,
      '#format' => $default_message['format'],
      '#default_value' => $default_message['value'],
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
        '#token_types' => ['node', 'workflow_transition', 'workflow_scheduled_transition', 'term', 'paragraph', 'comment'],
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    $form['recipients'] = [
      '#type' => 'details',
      '#title' => $this->t('Recipients'),
      '#open' => TRUE,
    ];
    // Add the author flag.
    $form['recipients']['author'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->entity->isAuthor(),
      '#title' => $this->t('Author'),
      '#description' => $this->t('Send to entity author/owner'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($wid = $this->entity->getWorkflowId()) {
      $this->entity->setWorkflowId($wid);
    }
    else {
      $this->entity->setWorkflowId($this->workflow->id());
    }
    parent::save($form, $form_state);
    drupal_set_message($this->t('Notification @label saved.', ['@label' => $this->entity->label()]));
  }

  /**
   * Machine name exists callback.
   *
   * @param string $id
   *   The machine name ID.
   *
   * @return bool
   *   TRUE if an entity with the same name already exists, FALSE otherwise.
   */
  public function exists($id) {
    $type = $this->entity->getEntityTypeId();
    return (bool) $this->entityManager->getStorage($type)->load($id);
  }

}
