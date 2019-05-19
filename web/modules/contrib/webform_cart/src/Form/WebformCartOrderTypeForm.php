<?php

namespace Drupal\webform_cart\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WebformCartOrderTypeForm.
 */
class WebformCartOrderTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webform_cart_order_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webform_cart_order_type->label(),
      '#description' => $this->t("Label for the Webform cart order entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webform_cart_order_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webform_cart\Entity\WebformCartOrderEntityType::load',
      ],
      '#disabled' => !$webform_cart_order_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform_cart_order_type = $this->entity;
    $status = $webform_cart_order_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Webform cart order entity type.', [
          '%label' => $webform_cart_order_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webform cart order entity type.', [
          '%label' => $webform_cart_order_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($webform_cart_order_type->toUrl('collection'));
  }

}
