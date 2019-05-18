<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines the product class attributes overview form.
 */
class ProductClassAttributesForm extends ObjectAttributesFormBase {

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

}
