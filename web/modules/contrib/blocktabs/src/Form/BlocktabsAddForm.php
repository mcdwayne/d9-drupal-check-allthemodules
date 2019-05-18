<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for blocktabs addition forms.
 */
class BlocktabsAddForm extends BlocktabsFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Blocktabs %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new blocktabs');

    return $actions;
  }

}
