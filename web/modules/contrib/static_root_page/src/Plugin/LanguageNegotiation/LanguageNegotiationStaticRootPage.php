<?php

namespace Drupal\static_root_page\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a language selection page.
 *
 * @LanguageNegotiation(
 *   id = Drupal\static_root_page\Plugin\LanguageNegotiation\LanguageNegotiationStaticRootPage::METHOD_ID,
 *   weight = -12,
 *   name = @Translation("Static root page"),
 *   description = @Translation("Static root page /"),
 * )
 */
class LanguageNegotiationStaticRootPage extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'static-root-page';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;
    // Negotiation is always "unsuccessful" except on / root page.
    if ($request->getRequestUri() === '/' && $this->languageManager) {
      $langcode = $this->config->get('language.negotiation')->get('selected_langcode');
    }
    return $langcode;
  }

}
