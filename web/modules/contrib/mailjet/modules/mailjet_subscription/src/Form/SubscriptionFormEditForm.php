<?php

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 *
 * Provides the add form for our Subscription Form.
 *
 * @ingroup mailjet_subscription
 */
class SubscriptionFormEditForm extends SubscriptionFormFormBase {

  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

}
