<?php

namespace Drupal\gtm_datalayer\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides add/edit form for condition instance forms.
 */
class ConditionEditForm extends ConditionAddForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtm_datalayer_condition_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $entity = NULL, $id = NULL) {
    return parent::buildForm($form, $form_state, $entity, $id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The @label conditions has been updated.', ['@label' => Unicode::strtolower($this->entity->label())]));
  }

}
