<?php

namespace Drupal\eid_auth\Authentication;

use BitWeb\IdServices\Authentication\IdCard\Authentication;
use Drupal\user\Entity\User;

/**
 * Class EidAuthentication.
 *
 * @package Drupal\eid_auth\Authentication
 */
class EidAuthentication extends Authentication {

  const SSL_CLIENT_VERIFY_SUCCESSFUL = 'SUCCESS';

  /**
   * {@inheritdoc}
   */
  public static function isSuccessful() {
    $ssl_verify = isset($_SERVER['SSL_CLIENT_VERIFY']) ? $_SERVER['SSL_CLIENT_VERIFY'] : $_SERVER['REDIRECT_SSL_CLIENT_VERIFY'];
    return $ssl_verify === self::SSL_CLIENT_VERIFY_SUCCESSFUL;
  }

  /**
   * {@inheritdoc}
   */
  public static function login() {
    $ssl_client = isset($_SERVER['SSL_CLIENT_S_DN']) ? $_SERVER['SSL_CLIENT_S_DN'] : $_SERVER['REDIRECT_SSL_CLIENT_S_DN'];

    $cardInfo = explode('/', $ssl_client);

    if (count($cardInfo) <= 1) {
      $cardInfo = explode(',', $ssl_client);
    }

    $parameters = [];
    foreach ($cardInfo as $info) {
      if ($info !== NULL) {
        $parameterArray = explode('=', $info);
        $parameters[$parameterArray[0]] = self::decodeToUtf8($parameterArray[1]);
      }
    }

    $user = self::findUserByPersonalIdCode($parameters['serialNumber']);

    if ($user) {
      user_login_finalize($user);
    }
  }

  /**
   * Find user by personal id code.
   *
   * @param string $personal_id_code
   *   User personal ID code.
   *
   * @return \Drupal\user\UserInterface|null
   *   User entity or null when not found.
   */
  public static function findUserByPersonalIdCode($personal_id_code) {
    $query = \Drupal::entityQuery('user');
    $query->condition('field_personal_id_code', $personal_id_code);
    $uids = $query->execute();

    return User::load(reset($uids));
  }

  /**
   * Smart ID response returns Personal ID code in PNO{country code}-XXXXXXXXXXX format.
   * Extract only ID numbers from code.
   *
   * @param $personal_id_code
   *
   * @return bool|string|null
   */
  public static function smartIdextractUserPersonalIdCode($personal_id_code) {

    $pos = strpos($personal_id_code, '-');

    $extracted_id = NULL;
    if ($pos !== FALSE) {

      $country_code = substr($personal_id_code, 3, 2);
      if ($country_code !== 'EE') {

        exit('Wrong country!');
      }

      $extracted_id = substr($personal_id_code, $pos + 1);
    }

    return $extracted_id;
  }

}
