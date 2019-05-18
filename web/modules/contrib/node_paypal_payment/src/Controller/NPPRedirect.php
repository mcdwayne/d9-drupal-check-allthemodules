<?php

namespace Drupal\node_paypal_payment\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Handles the "redirect" route.
 */
class NPPRedirect extends ControllerBase {

  /**
   * PayPal is redirecting the visitor here after the payment process.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirected to success page.
   */
  public function success($token) {
    $request = \Drupal::request();
    $config = $this->config('node_paypal_payment.settings');
    $success_message = $config->get('npp_success_message');

    $element = ['#markup' => $success_message . '<br>Transaction ID: ' . $request->get('txn_id')];

    if ($request->get('txn_id')) {

      $this->processResponse($token, $request);

      $nid = $request->get('item_number');
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $node->status = 1;
      $node->save();

      if ($config->get('npp_success_path') == 'node') {
        drupal_set_message($this->t('@message', ['@message' => $success_message . ' Transaction ID is ' . $request->get('txn_id')]));
        $url = $this->url('entity.node.canonical', ['node' => $nid]);
        return new RedirectResponse($url);
      }

      return $element;
    }
    else {
      return new RedirectResponse($this->url('<front>'));
    }
  }

  /**
   * Action for IPN url.
   */
  public function notify($token) {
    $element = [
      '#markup' => '',
    ];
    $request = \Drupal::request();
    $this->processResponse($token, $request);

    return $element;
  }

  /**
   * Action for cancel url.
   */
  public function cancel() {
    $config = $this->config('node_paypal_payment.settings');
    $cancel_message = $config->get('npp_cancel_message');

    $element = [
      '#markup' => $cancel_message,
    ];

    return $element;
  }

  /**
   * Method to update the payment details in databse.
   */
  public function processResponse($token, $request) {
    db_update('npp_payments')
      ->condition('token', $token)
      ->fields([
        'txn_id' => $request->get('txn_id'),
        'status' => $request->get('payment_status'),
        'payer_email' => $request->get('payer_email'),
        'payer_name' => $request->get('first_name') . ' ' . $request->get('last_name'),
        'payment_date' => $request->get('payment_date'),
      ])
      ->execute();
  }

}
