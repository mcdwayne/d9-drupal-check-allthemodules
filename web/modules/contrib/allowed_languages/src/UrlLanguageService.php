<?php

namespace Drupal\allowed_languages;

use Drupal\Core\Language\LanguageInterface;

/**
 * Allowed languages url language service.
 *
 * This service is a very dirty solution to a problem that does not seem to be
 * possible to resolve easily in another way.
 *
 * The problem that this service solves is to be able to know the language of
 * a URL when checking it's access, but access checks do not know the language
 * a route will be rendered in since this information is not available when
 * doing an access check.
 *
 * This service bridges this gap for the allowed languages module by allowing
 * a language to be stored before invoking access checks for URL objects, the
 * access checkers can then query the url language service to see the
 * language the URL will be rendered in.
 *
 * Be sure to re-set the stored language when URL access checks are done.
 *
 * @todo This service should probably be replaced by a better solution that
 * should preferably be added to core in some way.
 *
 * Maybe it would be possible to have a URL-object provide its options to the
 * access managers checkNamedRoute callback or something similar.
 */
class UrlLanguageService {

  /**
   * The temporarily stored language.
   *
   * @var \Drupal\Core\Language\LanguageInterface|null
   */
  private $storedLanguage = NULL;

  /**
   * Set the stored language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language to set.
   */
  public function setUrlLanguage(LanguageInterface $language) {
    $this->storedLanguage = $language;
  }

  /**
   * Get the stored language.
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
   *   Returns the set language.
   */
  public function getUrlLanguage() {
    return $this->storedLanguage;
  }

  /**
   * Reset the stored language.
   */
  public function resetUrlLanguage() {
    $this->storedLanguage = NULL;
  }

}
