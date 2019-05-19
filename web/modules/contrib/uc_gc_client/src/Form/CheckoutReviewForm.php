<?php

/**
 * @file
 * Contains \Drupal\uc_gc_client\Form\CheckoutReviewForm.
 */

namespace Drupal\uc_gc_client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uc_order\Entity\Order;

class CheckoutReviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_gc_client_checkout_review_form';
  }

  /**
   * Returns the elements for the checkout review form.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $order = NULL) {

    // Provide a submit button for the Checkout review form.
    $form['gocardless_link'] = array(
      '#type' => 'submit',
      '#value' => \Drupal::config('uc_gc_client.settings')->get('checkout_label'),
    );
    $form['#submit'][] = 'uc_gc_client_checkout_form_submit';

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {}

}

