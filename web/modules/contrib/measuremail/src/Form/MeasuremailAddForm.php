<?php

namespace Drupal\measuremail\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for measuremail addition forms.
 *
 * @internal
 */
class MeasuremailAddForm extends MeasuremailFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Measuremail %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new form');

    return $actions;
  }

}
