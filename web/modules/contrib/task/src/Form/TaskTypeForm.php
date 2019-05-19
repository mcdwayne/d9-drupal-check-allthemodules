<?php

namespace Drupal\task\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\task\TaskUtilities;

/**
 * Class TaskTypeForm.
 */
class TaskTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $task_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $task_type->label(),
      '#description' => $this->t("Label for the Task type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $task_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\task\Entity\TaskType::load',
      ],
      '#disabled' => !$task_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t("Description of the task type."),
    ];

    $form['allowed_statuses'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Statuses'),
      '#description' => $this->t(''),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getAllowedStatuses(),
      '#options' => TaskUtilities::getAllTaskStatuses(),
      'closed' => ['#disabled' => TRUE, '#checked' => TRUE],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $task_type = $this->entity;
    $statuses = $task_type->getAllowedStatuses();
    $statuses['closed'] = 'closed';
    $task_type->setAllowedStatuses($statuses);
    $status = $task_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Task type.', [
          '%label' => $task_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Task type.', [
          '%label' => $task_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($task_type->toUrl('collection'));
  }

}
