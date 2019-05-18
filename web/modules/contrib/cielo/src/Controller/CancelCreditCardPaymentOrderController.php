<?php

namespace Drupal\cielo\Controller;

use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Request\CieloError;
use Cielo\API30\Merchant;
use Drupal\cielo\Entity\CieloPayment;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CancelCreditCardPaymentOrder.
 *
 * @package Drupal\cielo\Services
 */
class CancelCreditCardPaymentOrderController extends ControllerBase {

  /**
   * Cancel an cedit card payment order.
   *
   * @param int $payment_id
   *   The cielo payment id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return to the previous page.
   */
  public function cancelOrder($payment_id) {

    $cielo_payment = CieloPayment::load($payment_id);
    $status = $cielo_payment->cancelCreditCardOrder();

    if (!$status instanceof \Exception) {
      \Drupal::messenger()->addMessage($this->t('Payment id %id has been canceled.', ['%id' => $payment_id]), 'status');
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Payment id %id could not be canceled. Error cod: %cod. Error message: %message.', [
        '%id' => $payment_id,
        '%code' => $status->getCode(),
        '%message' => $status->getMessage(),
      ]), 'error');
    }

    return new RedirectResponse($_SERVER['HTTP_REFERER']);

  }

}
