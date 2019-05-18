<?php

namespace Drupal\dpl\Form;

use Drupal\Core\Form\FormStateInterface;

class PreviewLinkAddForm extends PreviewLinkEditForm {
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Preview link config %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new');

    return $actions;
  }

}
