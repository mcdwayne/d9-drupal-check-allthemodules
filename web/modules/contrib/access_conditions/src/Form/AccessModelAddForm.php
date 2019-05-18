<?php

namespace Drupal\access_conditions\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides add form for access model instance forms.
 */
class AccessModelAddForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\access_conditions\Entity\AccessModelInterface $access_model */
    $access_model = $this->entity;

    $form = parent::form($form, $form_state);

    $form['#title'] = $this->t('Add access model');
    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $access_model->label(),
      '#description' => $this->t('The human-readable name of this access model. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $access_model->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\access_conditions\Entity\AccessModel', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this access model. It must only contain lowercase letters, numbers, and underscores.'),
    ];
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $access_model->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $status = $this->save($form, $form_state);

    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('The access model configuration has been saved.'));
    }
    $form_state->setRedirect('entity.access_model.edit_form', ['access_model' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Add conditions');

    return $actions;
  }

}
