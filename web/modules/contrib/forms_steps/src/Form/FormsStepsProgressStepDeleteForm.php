<?php

namespace Drupal\forms_steps\Form;

use Drupal\forms_steps\FormsStepsInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form to delete progress steps from FormsSteps entities.
 */
class FormsStepsProgressStepDeleteForm extends ConfirmFormBase {

  /**
   * The forms_steps entity the progress step being deleted belongs to.
   *
   * @var \Drupal\forms_steps\FormsStepsInterface
   */
  protected $formsSteps;

  /**
   * The progress step being deleted.
   *
   * @var string
   */
  protected $progressStepId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forms_steps_progress_step_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %progress_step from %forms_steps?', [
      '%progress_step' => $this->formsSteps->getProgressStep($this->progressStepId)
        ->label(),
      '%forms_steps' => $this->formsSteps->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->formsSteps->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\forms_steps\FormsStepsInterface $forms_steps
   *   The forms_steps entity being edited.
   * @param string|null $forms_steps_progress_step
   *   The forms_steps progress step being deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FormsStepsInterface $forms_steps = NULL, $forms_steps_progress_step = NULL) {
    if (!$forms_steps->hasProgressStep($forms_steps_progress_step)) {
      throw new NotFoundHttpException();
    }
    $this->formsSteps = $forms_steps;
    $this->progressStepId = $forms_steps_progress_step;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $forms_steps_label = $this->formsSteps->getProgressStep($this->progressStepId)
      ->label();
    $this->formsSteps
      ->deleteProgressStep($this->progressStepId)
      ->save();

    $this->messenger()->addMessage($this->t(
      'progress step %label deleted.',
      ['%label' => $forms_steps_label]
    ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
