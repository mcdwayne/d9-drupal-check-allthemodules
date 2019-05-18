<?php
namespace Drupal\commerce_bitpayir;

/**
 * Contains directives to connect to the Bitpay's payment gateway.
 *
 * We can use the class's methods to interact to Bitway gateway
 * in both actual and debugging mode.
 * Please see the documentation on @link http://bitpay.ir
 *
 * @package Drupal\commerce_bitpayir
 */
class BitpayGateway {

  /**
   * Verifies amount and api code.
   *
   * Checks if the api code and the amount is valid or not.
   * Then return an integer.
   *
   * @param string $url
   * @param string $api
   * @param integer $amount
   * @param string $redirect
   *   The page url in our program that the gateway must be redirected
   *   to it after a transaction done.
   * @return integer
   *   If a the number is greater than 0, then api code and amount
   *   is correct and our program must be redirected to gateway.
   *   If an error is occured we can check the error by
   *   checking error codes. If we get an error we must not redirect
   *   or program to the gateway.
   *   error code:
   *   -1 => api code is not compatible,
   *   -2 => order amount is not numeric or is below 1000 rial,
   *   -3 => redirect value is null,
   *   -4 => account associated with api is not found or is in review state.
   */
  public static function send($url, $api, $amount, $redirect) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&amount=$amount&redirect=$redirect");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
  }

  /**
   * Verifies $trans_id and $id_get.
   *
   * When a transaction is done in the Gateway, Bitpay
   * will redirects to the page we determined in the send function.
   * The gateway will post two parameters to that page, id_get and trans_id.
   * We must verify these two parameters via get function.
   * If these two parameters are valid, then we must save related payment in
   * our program.
   *
   * @param string $url
   * @param string $api
   * @param string $trans_id
   * @param string $id_get
   * @return integer
   *   success code:
   *   1 => Transaction is successfull and we
   *        can save the payment in our program
   *   error codes:
   *   -1 => api code is not compatible
   *   -2 => trans_id is not valid
   *   -3 => id_get is not valid
   *   -4 => transaction was not successful or not such a transaction
   */
  public static function get($url, $api, $trans_id, $id_get) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&id_get=$id_get&trans_id=$trans_id");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
  }
}