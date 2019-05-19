<?php

namespace Drupal\task\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaskStatusForm.
 */
class TaskStatusForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $task_status = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $task_status->label(),
      '#description' => $this->t("Label for the Task Status."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $task_status->id(),
      '#machine_name' => [
        'exists' => '\Drupal\task\Entity\TaskStatus::load',
      ],
      '#disabled' => !$task_status->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $task_status = $this->entity;
    $status = $task_status->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Task Status.', [
          '%label' => $task_status->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Task Status.', [
          '%label' => $task_status->label(),
        ]));
    }
    $form_state->setRedirectUrl($task_status->toUrl('collection'));
  }

}
