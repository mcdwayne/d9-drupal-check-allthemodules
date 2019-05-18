<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Attempts to resolve RFC 1766 locale for given customer.
 */
class DefaultLocaleResolver implements LocaleResolverInterface {

  protected $languageManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderInterface $order) : string {
    // By default we use current language to resolve locale.
    $isoLocale = $this->languageManager->getCurrentLanguage()->getId();

    return $this->getLocale($isoLocale);
  }

  /**
   * Attempts to map ISO locale to RFC 1766 locale.
   *
   * @param string $locale
   *   The locale.
   *
   * @return string
   *   The RFC 1766 locale.
   */
  protected function getLocale(string $locale) : string {
    $locale = strtolower($locale);

    $map = [
      'fi' => 'fi-fi',
      'sv' => 'sv-sv',
      'nb' => 'nb-no',
      'nn' => 'nn-no',
      'de' => 'de-de',
      // Drupal does not define these by default.
      'at' => 'de-at',
    ];

    return $map[$locale] ?? 'en-us';
  }

}
