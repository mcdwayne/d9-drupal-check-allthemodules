<?php

namespace Drupal\commerce_datatrans;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Helper methods for various Datatrans related tasks.
 */
class DatatransHelper {

  /**
   * Generates the server side sign to compare with the datatrans post data.
   *
   * @param string $hmac_key
   *   The hmac key.
   * @param string $merchant_id
   *   Unique Merchant Identifier (assigned by Datatrans).
   * @param string $amount
   *   Transaction amount in cents or smallest available unit of the currency.
   * @param string $currency
   *   Transaction currency – ISO Character Code (CHF, USD, EUR etc.).
   * @param string $reference_number
   *   Order id when sending data upstream, uppTransactionId when receiving daa.
   *
   * @return string
   *   The computed hash.
   */
  public static function generateSign($hmac_key, $merchant_id, $amount, $currency, $reference_number) {
    $hmac_data = $merchant_id . $amount . $currency . $reference_number;
    return hash_hmac('sha256', $hmac_data, pack('H*', $hmac_key));
  }

  /**
   * Datatrans error code mapping.
   *
   * @param int $code
   *   Provide error code from Datatrans callback.
   *
   * @return bool|FALSE|mixed|string
   *   Returns error message.
   */
  public static function mapErrorCode($code) {
    switch ($code) {
      case '1001':
        $message = t('Datrans transaction failed: missing required parameter.');
        break;

      case '1002':
        $message = t('Datrans transaction failed: invalid parameter format.');
        break;

      case '1003':
        $message = t('Datatrans transaction failed: value of parameter not found.');
        break;

      case '1004':
      case '1400':
        $message = t('Datatrans transaction failed: invalid card number.');
        break;

      case '1007':
        $message = t('Datatrans transaction failed: access denied by sign control/parameter sign invalid.');
        break;

      case '1008':
        $message = t('Datatrans transaction failed: merchant disabled by Datatrans.');
        break;

      case '1401':
        $message = t('Datatrans transaction failed: invalid expiration date.');
        break;

      case '1402':
      case '1404':
        $message = t('Datatrans transaction failed: card expired or blocked.');
        break;

      case '1403':
        $message = t('Datatrans transaction failed: transaction declined by card issuer.');
        break;

      case '1405':
        $message = t('Datatrans transaction failed: amount exceeded.');
        break;

      case '3000':
      case '3001':
      case '3002':
      case '3003':
      case '3004':
      case '3005':
      case '3006':
      case '3011':
      case '3012':
      case '3013':
      case '3014':
      case '3015':
      case '3016':
        $message = t('Datatrans transaction failed: denied by fraud management.');
        break;

      case '3031':
        $message = t('Datatrans transaction failed: declined due to response code 02.');
        break;

      case '3041':
        $message = t('Datatrans transaction failed: Declined due to post error/post URL check failed.');
        break;

      case '10412':
        $message = t('Datatrans transaction failed: PayPal duplicate error.');
        break;

      case '-885':
      case '-886':
        $message = t('Datatrans transaction failed: CC-alias update/insert error.');
        break;

      case '-887':
        $message = t('Datatrans transaction failed: CC-alias does not match with cardno.');
        break;

      case '-888':
        $message = t('Datatrans transaction failed: CC-alias not found.');
        break;

      case '-900':
        $message = t('Datatrans transaction failed: CC-alias service not enabled.');
        break;

      default:
        $message = t('Datatrans transaction failed: undefined error.');
        break;
    }

    return $message;
  }
}
