<?php

namespace Drupal\colours\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for colours addition forms.
 */
class ColoursAddForm extends ColoursFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Colours %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new Colours');

    return $actions;
  }

}
