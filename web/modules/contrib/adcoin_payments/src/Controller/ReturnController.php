<?php
/**
 * Controller for the page that the user returns to after payment.
 * @author appels
 */

namespace Drupal\adcoin_payments\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

use Drupal\adcoin_payments\Model\PageList;
use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\adcoin_payments\Model\Settings;
use Drupal\adcoin_payments\WalletAPIWrapper\PaymentGateway;
use Drupal\adcoin_payments\Exception\ExceptionHandler;
use Drupal\adcoin_payments\Exception\ReturnException;



class ReturnController extends ControllerBase {
  /**
   * {@inheritdoc}
   * Called when the user returns from the AdCoin Payment Gateway.
   */
  public function content() {
    try {
      // Make sure the required GET parameters are present
      if (!isset($_GET['status'], $_GET['id']))
        throw new ReturnException('Required GET parameters not provided.');

      // Fetch Wallet API key
      if (!($api_key = Settings::fetchApiKey()))
        throw new ReturnException('API key has not been configured.');

      // Retrieve payment metadata
      try {
        $gateway  = new PaymentGateway($api_key);
        $payment  = $gateway->getPayment($_GET['id']);
        $metadata = json_decode($payment['metadata'], true);
      } catch (\Exception $e) {
        throw new ReturnException($e->getMessage());
      }

      // Act based on status GET parameter
      if ('pending' === $_GET['status']) {
        // Mark payment as paid (unconfirmed)
        PaymentStorage::paymentUpdateStatus($_GET['id'], PaymentStorage::$PAID_UNCONFIRMED);
        $url = Url::fromRoute($metadata['route_success'])->toString();
      } else
        $url = Url::fromRoute($metadata['route_cancel'])->toString();

      // Redirect user to the URL if it was set
      if (empty($url)) {
        return [ '#markup' => t('Thank you for choosing to pay through AdCoin. '
                               .'Please wait for your order to be confirmed.')
        ];
      }
      $response = new RedirectResponse($url);
      $response->send();
      return [ '#markup' => '' ];

    } catch (ReturnException $e) {
      return ExceptionHandler::handle($e);
    }
  }



  public function content_success($payment_id = NULL) {
    return [
      '#markup' => t('Your payment is about to be confirmed. This might take up to a few hours.')
    ];
  }

  public function content_failed($payment_id = NULL) {
    return [
      '#markup' => t('Please try again.')
    ];
  }
}