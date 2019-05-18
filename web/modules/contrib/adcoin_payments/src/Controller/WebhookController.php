<?php
/**
 * Controller for the page that the AdCoin Payment Gateway calls once a payment
 * has been confirmed or has timed out.
 * @author appels
 */

namespace Drupal\adcoin_payments\Controller;

use Drupal\adcoin_payments\Exception\ExceptionHandler;
use Drupal\adcoin_payments\Exception\WebhookException;
use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\adcoin_payments\Model\Settings;

use Drupal\Core\Controller\ControllerBase;


class WebhookController extends ControllerBase {
  /**
   * Determines whether the required POST fields are present.
   *
   * @return bool
   */
  private static function isPostDataPresent() {
    return isset(
      $_POST['id'],
      $_POST['created_at'],
      $_POST['status'],
      $_POST['metadata'],
      $_POST['hash']
    );
  }

  /**
   * Determines whether the provided hash in POST matches the POST data.
   *
   * @param string $api_key AdCoin Wallet API key.
   *
   * @return bool
   */
  private static function isHashCorrect($api_key) {
    $http_query = http_build_query([
      'id'         => $_POST['id'],
      'created_at' => $_POST['created_at'],
      'status'     => $_POST['status'],
      'metadata'   => stripslashes($_POST['metadata'])
    ]);
    $query_hash = hash_hmac('sha512', $http_query, $api_key);

    return ($_POST['hash'] == $query_hash);
  }

  /**
   * {@inheritdoc}
   */
  public function content() {

    \Drupal::logger('adcoin_payments')->notice('Webhook callback with status "'.$_POST['status'].'".');

    try {
      // Make sure the required POST data is present
      if (!self::isPostDataPresent())
        throw new WebhookException('Request lacks required POST data.');

      // Fetch API key
      if (!($api_key = Settings::fetchApiKey()))
        throw new WebhookException('API key has not been configured.');

      // Check whether provided hash matches POST data
      if (!self::isHashCorrect($api_key))
        throw new WebhookException('Callback hash does not match given data.');

      // Perform action based on given payment status
      switch ($_POST['status']) {
        case 'paid':
          // Mark the payment as confirmed
          PaymentStorage::paymentUpdateStatus($_POST['id'], PaymentStorage::$PAID_CONFIRMED);
          break;

        case 'timed_out':
          // Mark the payment as timed out
          PaymentStorage::paymentUpdateStatus($_POST['id'], PaymentStorage::$TIMED_OUT);
          break;

        default:
          throw new WebhookException('Invalid status "' . $_POST['status'] . '".');
      }

      // Webhook callback success
      \Drupal::logger('adcoin_payments')->notice('Payment confirmed! (id = '.$_POST['id'].')');
      $build = [
        '#markup' => ''
      ];
      return $build;

    } catch (\Exception $e) {
      return ExceptionHandler::handle($e);
    }
  }

}