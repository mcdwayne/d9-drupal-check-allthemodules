<?php

namespace Drupal\subscription_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class subscriptionTypeForm.
 *
 * @package Drupal\subscription_entity\Form
 */
class SubscriptionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $subscription_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $subscription_type->label(),
      '#description' => $this->t("Label for the Subscription type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $subscription_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\subscription_entity\Entity\subscriptionType::load',
      ],
      '#disabled' => !$subscription_type->isNew(),
    ];

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role to assign'),
      '#description' => $this->t('A role for the user to have when they have this subscription. If you do not want a role assigned use the authenticated user role'),
      '#options' => $subscription_type->getSiteRoles(),
      '#default_value' => $subscription_type->getRole(),
      '#required' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $subscription_type = $this->entity;
    $status = $subscription_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Subscription type.', [
          '%label' => $subscription_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Subscription type.', [
          '%label' => $subscription_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($subscription_type->toUrl('collection'));
  }

}
