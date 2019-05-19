<?php

namespace Drupal\subscription_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SubscriptionTermTypeForm.
 *
 * @package Drupal\subscription_entity\Form
 */
class SubscriptionTermTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $subscription_term_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $subscription_term_type->label(),
      '#description' => $this->t("Label for the Subscription Term type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $subscription_term_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\subscription_entity\Entity\SubscriptionTermType::load',
      ],
      '#disabled' => !$subscription_term_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $subscription_term_type = $this->entity;
    $status = $subscription_term_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Subscription Term type.', [
          '%label' => $subscription_term_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Subscription Term type.', [
          '%label' => $subscription_term_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($subscription_term_type->toUrl('collection'));
  }

}
