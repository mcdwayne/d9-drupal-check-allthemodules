<?php

namespace Drupal\forms_steps\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class FormsStepsStepEditForm.
 */
class FormsStepsStepEditForm extends FormsStepsStepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forms_steps_step_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['id']['#default_value'] = $this->stepId;
    $form['id']['#disabled'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\forms_steps\FormsStepsInterface $forms_steps */
    $forms_steps = $this->entity;

    $forms_steps->save();
    $this->messenger()->addMessage($this->t('Saved %label step.', [
      '%label' => $forms_steps->getStep($this->stepId)->label(),
    ]));
    $form_state->setRedirectUrl($forms_steps->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];

    $actions['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#access' => $this->entity->access('delete-state:' . $this->stepId),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#url' => Url::fromRoute('entity.forms_steps.delete_step_form', [
        'forms_steps' => $this->entity->id(),
        'forms_steps_step' => $this->stepId,
      ]),
    ];

    return $actions;
  }

}
