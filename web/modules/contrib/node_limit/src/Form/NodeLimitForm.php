<?php

namespace Drupal\node_limit\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the NodeLimitForm form.
 */
class NodeLimitForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $node_limit = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $node_limit->label(),
      '#description' => $this->t("Label for the NodeLimit."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $node_limit->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\node_limit\Entity\NodeLimit::load',
      ),
      '#disabled' => !$node_limit->isNew(),
    );

    // You will need additional form elements for your custom properties.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $node_limit = $this->entity;
    $status = $node_limit->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label NodeLimit.', array(
        '%label' => $node_limit->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label NodeLimit was not saved.', array(
        '%label' => $node_limit->label(),
      )));
    }
    $form_state->setRedirect('entity.node_limit.list');
  }

}
