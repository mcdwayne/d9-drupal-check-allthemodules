<?php

namespace Drupal\bills\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BillsTypeForm.
 */
class BillsTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $bills_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bills_type->label(),
      '#description' => $this->t("Label for the Bills type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bills_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bills\Entity\BillsType::load',
      ],
      '#disabled' => !$bills_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bills_type = $this->entity;
    $status = $bills_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Bills type.', [
          '%label' => $bills_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Bills type.', [
          '%label' => $bills_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($bills_type->toUrl('collection'));
  }

}
