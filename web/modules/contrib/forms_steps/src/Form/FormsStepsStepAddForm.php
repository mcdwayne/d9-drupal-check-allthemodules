<?php

namespace Drupal\forms_steps\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormsStepsStepAddForm.
 */
class FormsStepsStepAddForm extends FormsStepsStepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forms_steps_step_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\forms_steps\FormsStepsInterface $forms_steps */
    $forms_steps = $this->entity;
    $forms_steps->save();

    // TODO: Check if there is a way to just update the current route ?!
    /** @var \Drupal\Core\Routing\RouteBuilder $routeBuilderService */
    $routeBuilderService = \Drupal::service('router.builder');
    $routeBuilderService->rebuild();

    $this->messenger()->addMessage($this->t('Created %label step.', [
      '%label' => $forms_steps->getStep($form_state->getValue('id'))
        ->label(),
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
    return $actions;
  }

}
