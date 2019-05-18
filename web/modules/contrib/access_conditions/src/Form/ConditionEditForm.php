<?php

namespace Drupal\access_conditions\Form;

use Drupal\access_conditions\Entity\AccessModelInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides add/edit form for condition instance forms.
 */
class ConditionEditForm extends ConditionAddForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_conditions_condition_edit';
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function buildForm(array $form, FormStateInterface $form_state, AccessModelInterface $access_model = NULL, $id = NULL) {
    return parent::buildForm($form, $form_state, $access_model, $id);
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The access model condition has been updated.'));
  }

}
