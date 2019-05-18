<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 8/17/16
 * Time: 11:12 AM
 */

namespace Drupal\lightspeed_ecom\Service;


use Drupal\lightspeed_ecom\ShopInterface;

interface SecurityTokenGeneratorInterface {

  /**
   * Generate a token based on $value, the shop and site's private keys.
   *
   * @param \Drupal\lightspeed_ecom\ShopInterface $shop
   *   The shop to generate a token for.
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return mixed
   */
  public function get(ShopInterface $shop, $value = '');

  /**
   * Validates a token based on $value, the shop and site's private keys.
   *
   * @param string $token
   *   The token to be validated.
   * @param \Drupal\lightspeed_ecom\ShopInterface $shop
   *   The shop to validate a token for.
   * @param string $value
   *   (optional) An additional value to base the token on.
   *
   * @return mixed
   */
  public function validate($token, ShopInterface $shop, $value = '');

}
