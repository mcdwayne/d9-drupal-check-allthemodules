<?php

namespace Drupal\lightspeed_ecom\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\lightspeed_ecom\ShopDisabledException;
use Drupal\lightspeed_ecom\ShopInterface;

/**
 * Lightspeed eCom API client factory.
 *
 * @package Drupal\lightspeed_ecom
 */
class ApiClientFactory implements ApiClientFactoryInterface {


  /**
   * The entity manager used by the factory.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager used by the factory.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected  $languageManager;

  static protected $clients = [];

  /**
   * ApiClientFactory constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for the factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager for the factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient($shop = 'default', LanguageInterface $language = NULL) {
    if ($language == NULL) {
      $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    }
    $language_id = $language->getId();
    if (is_string($shop)) {
      $shop_id = $shop;
      /** @var ShopInterface $shop */
      $shop = $this->entityTypeManager->getStorage('lightspeed_ecom_shop')
        ->load($shop_id);
      if (!$shop) {
        throw new ShopNotDefinedException('The specified Lightspeed eCom shop is not defined: ' . $hop_id);
      }
      if (!$shop->status()) {
        throw new ShopDisabledException('The specified Lightspeed eCom shop is disabled: ' . $shop_id);
      }
    }
    if (empty(self::$clients[$shop->id()][$language_id])) {
      // If necessary, a new client instance is created.
      self::$clients[$shop->id()][$language_id] = new \WebshopappApiClient($shop->get('cluster_id'), $shop->get('api_key'), $shop->get('api_secret'), $language->getId());
    }
    return self::$clients[$shop->id()][$language_id];
  }

}
