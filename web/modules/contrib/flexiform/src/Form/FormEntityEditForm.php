<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\flexiform\MultipleEntityFormState;

/**
 * Provides the entity edit form.
 */
class FormEntityEditForm extends FormEntityBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexiform_form_entity_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlexiformEntityFormDisplayInterface $form_display = NULL, $entity_namespace = '') {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    $form = parent::buildForm($form, $form_state, $form_display);
    $form_entity = $this->formEntityManager($form_state)->getFormEntity($entity_namespace);

    return $this->buildConfigurationForm($form, $form_state, $form_entity, $entity_namespace);
  }

}
