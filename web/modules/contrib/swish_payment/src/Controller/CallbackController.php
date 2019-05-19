<?php

namespace Drupal\swish_payment\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\swish_payment\Entity\SwishTransaction;

/**
 * Class CallbackController.
 *
 * @package Drupal\swish_payment\Controller
 */
class CallbackController extends ControllerBase {

  /**
   * Complete. This is called by Swish service provider top update payment status on our side.
   *
   * @return string
   *   Return Hello string.
   */
  public function update() {
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    if($request->id) {
      if($st = SwishTransaction::Load($request->id)) {
        $st->setErrorCode($request->errorCode);
        $st->setErrorMessage($request->errorMessage);
        $st->setPaymentReference($request->paymentReference);
        $st->setStatus($request->status);
        $st->setCreatedTime(strtotime($request->dateCreated));
        $st->setPaidTime(strtotime($request->datePaid));
        $st->save();
      }
    }
    return new JsonResponse(true);
  }

  /**
   * Pending payment. This is the page that display the pengind payment information.
   *
   * @return string
   *   Return Hello string.
   */
  public function pending($trans_id) {
    $destination = $_GET['destination'];
    if(empty($destination))
      $destination = Url::fromRoute("swish_payment.callback_complete", ['trans_id' => $trans_id])->toString();
    $loading = drupal_get_path('module', 'swish_payment') . '/assets/img/loading.gif';
    return [
      '#title' => $this->t('Waiting for payment'),
      '#attached' => [
        'library' => ['swish_payment/swish-payment-pending'],
        'drupalSettings' => ['transactionId' => $trans_id, 'destination' => $destination],
      ],
      '#type' => 'markup',
      '#markup' => "<p>" . $this->t('Please open the Swish App and complete the payment.') . "</p><p><img src=\"/$loading\" class=\"swish-payment-loading\" /></p>",
    ];
  }

  /**
   * Poll status. This is called from our pending page to see if the status has changed.
   *
   * @return string
   *   Return Hello string.
   */
  public function poll($trans_id) {
    $return = ['status' => false ];
    if($st = SwishTransaction::Load($trans_id))
      $return = $st->toArray();
    return new JsonResponse($return);
  }

  /**
   * Error page.
   *
   * @return string
   *   Return Error message string.
   */
  public function error() {
    return [
      '#title' => $this->t('An error occured'),
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Check you phone if you have any penging payment blocking this payment.') . '</p>',
    ];
  }

  /**
   * Done page.
   *
   * @return string
   *   Return Complete page
   */
  public function complete($trans_id) {
    return [
      '#title' => $this->t('Payment complete'),
      '#type' => 'markup',
      '#markup' => "<p>" . $this->t('Your Swish payment was completed successfully. Thanks!') . "</p>",
    ];
  }
}
