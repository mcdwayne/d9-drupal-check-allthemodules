<?php

namespace Drupal\price\Resolver;

/**
 * Defines the interface for locale resolvers.
 */
interface LocaleResolverInterface {

  /**
   * Resolves the locale.
   *
   * @return \Drupal\price\Locale|null
   *   The locale object, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve();

}
