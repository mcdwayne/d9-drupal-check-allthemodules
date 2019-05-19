<?php

namespace Drupal\user_restrictions\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for image style addition forms.
 */
class UserRestrictionsAddForm extends UserRestrictionsFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('User restriction was created for %label.', ['%label' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new user restriction');

    return $actions;
  }

}
