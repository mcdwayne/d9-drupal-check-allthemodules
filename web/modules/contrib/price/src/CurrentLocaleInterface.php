<?php

namespace Drupal\price;

/**
 * Holds a reference to the current locale, resolved on demand.
 *
 * @see \Drupal\price\CurrentLocale
 */
interface CurrentLocaleInterface {

  /**
   * Gets the locale for the current request.
   *
   * @return \Drupal\price\Locale
   *   The locale.
   */
  public function getLocale();

}
