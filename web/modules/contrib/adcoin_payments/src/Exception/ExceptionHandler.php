<?php
/**
 * Global exception handler class.
 * @author appels
 */

namespace Drupal\adcoin_payments\Exception;
use Drupal\adcoin_payments\Exception\ReturnException;
use Drupal\adcoin_payments\Exception\WebhookException;
use Drupal\adcoin_payments\Exception\DatabaseException;
use Drupal\adcoin_payments\Exception\SubmissionException;

class ExceptionHandler {
  /**
   * Choose handler action based on given exception.
   *
   * @param $e The exception to handle.
   *
   * @return mixed Possible return data from the handler action.
   */
  public static function handle($e) {
    try {
      throw $e;
    } catch (WebhookException $e) {
      $msg = $e->getMessage();
      \Drupal::logger('adcoin_payments')->error('Webhook error: ' . $msg);
      return ['#markup' => $msg];
      // return self::handleWebhookException($e);
    } catch (ReturnException $e) {
      \Drupal::logger('adcoin_payments')->error('Return error: ' . $e->getMessage());
      return ['#markup' => t('An error occurred.')];

    } catch (SubmissionException $e) {
      \Drupal::logger('adcoin_payments')->error('Submission error: ' . $e->getMessage());
      return;

    } catch (DatabaseException $e) {
      \Drupal::logger('adcoin_payments')->error('Database error: ' . $e->getMessage());
      return;

    }
  }

  // /**
  //  * Act based on the given webhook exception.
  //  *
  //  * @param WebhookException $e
  //  *
  //  * @return mixed Possible return data.
  //  */
  // private static function handleWebhookException(WebhookException $e) {
  //   try {
  //     throw $e;
  //   } catch (\LackingPostDataException $e) {
  //     return ['#markup' => $e->getMessage()];
  //   }
  // }
}