<?php

namespace Drupal\decoupled_quiz\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ResultTypeForm.
 */
class ResultTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $result_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $result_type->label(),
      '#description' => $this->t("Label for the Result type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $result_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\decoupled_quiz\Entity\ResultType::load',
      ],
      '#disabled' => !$result_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result_type = $this->entity;
    $status = $result_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Result type.', [
          '%label' => $result_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Result type.', [
          '%label' => $result_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($result_type->toUrl('collection'));
  }

}
