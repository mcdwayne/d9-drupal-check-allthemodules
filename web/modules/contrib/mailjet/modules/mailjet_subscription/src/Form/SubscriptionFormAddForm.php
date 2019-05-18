<?php

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 *
 * Provides the add form for our Subscription Form.
 *
 * @ingroup mailjet_subscription
 */
class SubscriptionFormAddForm extends SubscriptionFormFormBase {

  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create a Subscription Form');
    return $actions;
  }

}
