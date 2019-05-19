<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines the product class attribute add form.
 */
class ProductClassAttributesAddForm extends ObjectAttributesAddFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeTypeInterface $node_type = NULL) {
    $this->attributeTable = 'uc_class_attributes';
    $this->optionTable = 'uc_class_attribute_options';
    $this->idField = 'pcid';
    $this->idValue = $node_type->id();
    $this->attributes = uc_class_get_attributes($this->idValue);

    return parent::buildBaseForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.node_type.edit_form', ['node_type' => $this->idValue]);
  }

}
