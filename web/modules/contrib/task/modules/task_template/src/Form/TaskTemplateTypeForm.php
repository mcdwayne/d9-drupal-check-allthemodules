<?php

namespace Drupal\task_template\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaskTemplateTypeForm.
 */
class TaskTemplateTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $task_template_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $task_template_type->label(),
      '#description' => $this->t("Label for the Task Template type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $task_template_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\task_template\Entity\TaskTemplateType::load',
      ],
      '#disabled' => !$task_template_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $task_template_type = $this->entity;
    $status = $task_template_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Task Template type.', [
          '%label' => $task_template_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Task Template type.', [
          '%label' => $task_template_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($task_template_type->toUrl('collection'));
  }

}
