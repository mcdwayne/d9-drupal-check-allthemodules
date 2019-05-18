<?php
/**
 * Created by PhpStorm.
 * User: buyle
 * Date: 8/16/16
 * Time: 8:58 AM
 */
namespace Drupal\lightspeed_ecom\Service;
use Drupal\Core\Language\LanguageInterface;
use Drupal\lightspeed_ecom\Entity\Shop;


/**
 * Lightspeed eCom API client factory.
 *
 * @package Drupal\lightspeed_ecom
 */
interface ApiClientFactoryInterface {
  /**
   * Gets the client object for the specified shop and language.
   *
   * Clients instances are created as needed and cached statically (ie. the same
   * instances is returned whenever a client is requested for the same
   * shop and language).
   *
   * @param string|shop $shop
   *   The ID of the Lightspeed eCom shop to use, or the shop itself.
   * @param \Drupal\Core\Language\LanguageInterface|null $language
   *   The language to use the api in. Defaults to current content language.
   *
   * @throws \Drupal\lightspeed_ecom\shopNotDefinedException
   *   If the specific shop is not defined.
   * @throws \Drupal\lightspeed_ecom\shopDisabledException
   *   If the specific shop is not enabled.
   *
   * @return \WebshopappApiClient
   *   The API client instance for the specified shop and language.
   */
  public function getClient($shop = 'default', LanguageInterface $language = NULL);
}
