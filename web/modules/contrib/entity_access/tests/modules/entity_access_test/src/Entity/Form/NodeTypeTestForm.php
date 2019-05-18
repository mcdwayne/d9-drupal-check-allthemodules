<?php

namespace Drupal\entity_access_test\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class NodeTypeTestForm.
 */
class NodeTypeTestForm extends FormBase {

  use TestForm;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_type_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeType $node_type = NULL) {
    $form += $this->elements($form, $form_state, $node_type->bundle());
    $form['actions'] = ['#type' => 'actions'] + $this->actions($form, $form_state);

    return $form;
  }

}
