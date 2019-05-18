<?php

namespace Drupal\cli_language\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Selects a language based on the CLI environment.
 *
 * @LanguageNegotiation(
 *   id = "cli_language",
 *   name = @Translation("CLI language"),
 *   description = @Translation("A configurable language for when the site is used through a CLI."),
 *   config_route_name = "cli_language.negotiation_cli_language"
 * )
 */
class Cli extends LanguageNegotiationMethodBase {

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    if (PHP_SAPI == 'cli') {
      return $this->config->get('cli_language.negotiation')->get('language_code');
    }
    return FALSE;
  }

}
