<?php

namespace Drupal\commerce_vantiv;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use litle\sdk\XmlParser as LitleXmlParser;

/**
 * Class VantivApiHelper.
 *
 * @package commerce_vantiv
 */
class VantivApiHelper {

  /**
   * Gets authorization transaction expiration timestamp.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   Drupal Commerce payment transaction.
   *
   * @return int
   *   Timestamp when authorization expires.
   */
  public static function getAuthorizationExpiresTime(PaymentInterface $payment) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface $payment_method */
    $payment_method = $payment->getPaymentMethod();
    $card_type = $payment_method->card_type->value;
    return REQUEST_TIME + self::getPaymentAuthorizationLifespan($card_type);
  }

  /**
   * Gets authorization expiration time for a given card type.
   *
   * @param string $commerce_card_type
   *   Drupal Commerce credit card type.
   *
   * @return int
   *   Number of seconds until expiration.
   */
  public static function getPaymentAuthorizationLifespan($commerce_card_type) {
    $day = 86400;
    $vantiv_card_type = self::getVantivCreditCardType($commerce_card_type);
    switch ($vantiv_card_type) {
      case 'DI':
        return 10 * $day;

      case 'AX':
      case 'MC':
      case 'VI':
      default:
        return 7 * $day;
    }
  }

  /**
   * Gets a Vantiv formatted credit card expiration date.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @return string
   *   Credit card expiration date formatted 'MMYY'.
   */
  public static function getVantivCreditCardExpDate(PaymentMethodInterface $payment_method) {
    $month = $payment_method->get('card_exp_month')->value;
    $year = $payment_method->get('card_exp_year')->value;
    return str_pad($month, 2, 0, STR_PAD_LEFT) . substr($year, -2);
  }

  /**
   * Gets a Vantiv credit card type for a given Commerce credit card type.
   *
   * @param string $commerce_type
   *   Drupal Commerce credit card type code.
   *
   * @return string|bool
   *   Vantiv credit card type code or FALSE if not found.
   */
  public static function getVantivCreditCardType($commerce_type) {
    $vantiv_types = [
      'amex' => 'AX',
      'dci' => 'DC',
      'dc' => 'DC',
      'discover' => 'DI',
      'jcb' => 'JC',
      'mastercard' => 'MC',
      'visa' => 'VI',
    ];
    if (isset($vantiv_types[$commerce_type])) {
      return $vantiv_types[$commerce_type];
    }
    return FALSE;
  }

  /**
   * Gets a Drupal Commerce credit card type code for a given Vantiv card type.
   *
   * @param string $vantiv_type
   *   Vantiv credit card type code.
   *
   * @return string|bool
   *   Drupal Commerce credit card type code or FALSE if not found.
   */
  public static function getCommerceCreditCardType($vantiv_type) {
    $commerce_types = [
      'AX' => 'amex',
      'DC' => 'dc',
      'DI' => 'discover',
      'JC' => 'jcb',
      'MC' => 'mastercard',
      'VI' => 'visa',
    ];
    if (isset($commerce_types[$vantiv_type])) {
      return $commerce_types[$vantiv_type];
    }
    return FALSE;
  }

  /**
   * Gets a Vantiv formatted amount from a decimal amount.
   *
   * @param string $amount
   *   Decimal amount.
   *
   * @return string
   *   Deimal amount with no decimal point, to a scale of 2 (hundredths digits).
   *
   *   For example:
   *     - 22.00 becomes 2200
   *     - 22 becomes 2200
   */
  public static function getVantivAmountFormat($amount) {
    // Commerce Price Items are decimal to a scale of 6, we need a scale of 2.
    // Sometimes, as in a payment (admin) form, the amount will be scale of 2.
    if (substr($amount, -7, 1) === '.') {
      $amount = substr($amount, 0, -4);
    }
    // Vantiv does not want decimal points in their price amounts.
    if (strpos($amount, '.') !== FALSE) {
      $amount = str_replace('.', '', $amount);
    }

    return $amount;
  }

  /**
   * Prepares values for Vantiv requests from gateway config values.
   *
   * @param array $config
   *   Payment gateway configuration values.
   *
   * @return array
   *   An array of values required for Vantiv requests.
   */
  public static function getApiRequestParamsFromConfig(array $config) {
    $config['reportGroup'] = $config['report_group'];
    $config['merchantId'] = $config['currency_merchant_map']['default'];
    unset($config['report_group']);
    unset($config['currency_merchant_map']);

    if ($config['mode'] == 'live') {
      $config['url'] = 'https://payments.vantivcnp.com/vap/communicator/online';
    }
    elseif ($config['mode'] == 'post-live') {
      $config['url'] = 'https://payments.vantivpostlive.com/vap/communicator/online';
    }
    else {
      $config['url'] = 'https://payments.vantivprelive.com/vap/communicator/online';
    }

    return $config;
  }

  /**
   * Gets a normalized response array from an XML response document element.
   *
   * @param \DomDocument $response_document
   *   Vantiv XML response document.
   * @param string $payload_attribute
   *   The id of the DomDocumentFragment to retrieve from the response document.
   *
   * @return array|bool
   *   An array of response elements with '@' prefixes removed from some keys.
   */
  public static function getResponseArray(\DomDocument $response_document, $payload_attribute) {
    $payload = LitleXmlParser::getDomDocumentAsString($response_document);
    $xml = simplexml_load_string($payload);
    $json = json_encode($xml);
    $json_dec = json_decode($json, TRUE);
    if (!empty($json_dec[$payload_attribute])) {
      $attributes = [];
      $new_array = [];
      foreach ($json_dec[$payload_attribute] as $key => $value) {
        if (strstr($key, '@') != FALSE) {
          $new_key = str_replace('@', '', $key);
          $new_array[$new_key] = $value;
        }
        else {
          $new_array[$key] = $value;
        }
      }
      foreach ($new_array['attributes'] as $k => $v) {
        if (!empty($k) and !empty($v)) {
          $attributes[$k] = $v;
        }
      }
      $new_array['attributes'] = $attributes;
      return $new_array;
    }
    elseif (!empty($json_dec['@attributes'])) {
      return $json_dec['@attributes'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns TRUE if a response is a success based on its success code.
   *
   * @param int|string $response_code
   *   The Vantiv response code.
   *
   * @return bool
   *   TRUE if response code is one of Vantiv's success codes.
   */
  public static function isResponseSuccess($response_code) {
    return in_array($response_code, [
      '000', '801', '802',
    ]);
  }

}
