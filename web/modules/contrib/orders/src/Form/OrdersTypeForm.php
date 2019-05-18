<?php

namespace Drupal\orders\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrdersTypeForm.
 */
class OrdersTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $orders_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $orders_type->label(),
      '#description' => $this->t("Label for the Orders type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $orders_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\orders\Entity\OrdersType::load',
      ],
      '#disabled' => !$orders_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $orders_type = $this->entity;
    $status = $orders_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Orders type.', [
          '%label' => $orders_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Orders type.', [
          '%label' => $orders_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($orders_type->toUrl('collection'));
  }

}
