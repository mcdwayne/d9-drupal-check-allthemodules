<?php

/**
 * @file
 * Contains \Drupal\register_user_with_stripe_payment\Form\StripeCharge.
 */

namespace Drupal\register_user_with_stripe_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class used form "pay with card" form with help of stript external library.
 */
class StripeCharge extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'register_user_with_stripe_payment_charge_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $mail = NULL, $stripe = NULL, $amount = NULL) {
    $form = array();
    $form['#action'] = $this->url('register_user_with_stripe_payment.stripe_complete', ['uid' => $uid], ['absolute' => TRUE]);
    $form['pay_button'] = array(
      '#type' => 'markup',
      '#markup' => new FormattableMarkup('<p><script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
    data-key="' . $stripe['publishable_key'] . '" data-amount="' . $amount . '" data-description="User Registration" data-email="' . $mail . '"></script></p>', []),
    );
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

}
