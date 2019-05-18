<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 8/17/16
 * Time: 11:11 AM
 */

namespace Drupal\lightspeed_ecom\Service;


use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\lightspeed_ecom\ShopInterface;

/**
 * Security token generator for Lightspeed shops.
 *
 * The CSRF Token service cannot be used for this task as its token are tied to
 * the users' sessions.
 *
 * @package Drupal\lightspeed_ecom\Service
 */
class SecurityTokenGenerator implements SecurityTokenGeneratorInterface {

  /** @var \Drupal\Core\PrivateKey */
  protected $privateKey;

  /**
   * Create a new security token generator.
   *
   * @param $privateKey
   *   The Drupal private key to use for token.
   */
  public function __construct(PrivateKey $privateKey) {
    $this->privateKey = $privateKey;
  }


  /**
   * {@inheritdoc}
   */
  public function get(ShopInterface $shop, $value = '') {
    return $this->computeToken($shop, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($token, ShopInterface $shop, $value = '') {
    return $token === $this->computeToken($shop, $value);
  }

  /**
   * Generates a token based on $value, the shop and site's private keys.
   */
  protected function computeToken(ShopInterface $shop, $value = '') {
    if (!$value) {
      $value = $shop->apiKey();
    }
    return Crypt::hmacBase64($value, $shop->apiSecret() . $this->privateKey->get() . Settings::getHashSalt());
  }

}
