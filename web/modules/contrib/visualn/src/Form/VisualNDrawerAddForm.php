<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for visualn drawer addition forms.
 */
class VisualNDrawerAddForm extends VisualNDrawerFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Subdrawer %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new subdrawer');

    return $actions;
  }

}
