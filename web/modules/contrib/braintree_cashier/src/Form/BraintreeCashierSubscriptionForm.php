<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Subscription edit forms.
 *
 * @ingroup braintree_cashier
 */
class BraintreeCashierSubscriptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\braintree_cashier\Entity\BraintreeCashierSubscription */
    $form = parent::buildForm($form, $form_state);

    $form['braintree_subscription_id']['#states'] = [
      'enabled' => [
        ':input[name=subscription_type]' => [
          ['value' => BraintreeCashierSubscriptionInterface::PAID_INDIVIDUAL],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Subscription.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Subscription.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.braintree_cashier_subscription.canonical', ['braintree_cashier_subscription' => $entity->id()]);
  }

}
