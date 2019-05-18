<?php

namespace Drupal\suggestion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\suggestion\SuggestionHelper;
use Drupal\suggestion\SuggestionStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;

/**
 * Suggestion menu callback.
 */
class SuggestionController extends ControllerBase {

  /**
   * AJAX search autocomplete callback.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @return object
   *   A JSON response of search suggestions.
   */
  public function autoComplete(Request $request) {
    $cfg = SuggestionHelper::getConfig();
    $json = [];
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $txt = preg_replace(['/^[^a-z]+/u', '/[^a-z]+$/u'], ['', ' '], Unicode::strtolower($request->query->get('q')));

    if (strlen($txt) < $cfg->min) {
      return new JsonResponse([]);
    }
    // UTF-8 safe word count.
    $count = count(preg_split('/\s+/', $txt)) + 2;
    $atoms = ($count < $cfg->atoms_min) ? $cfg->atoms_min + 2 : $count + 2;

    $ngram = db_like($txt) . '%';

    $suggestions = SuggestionStorage::getAutocomplete($ngram, $atoms, $cfg->limit, $language);

    if (count($suggestions) < $cfg->limit) {
      $suggestions += SuggestionStorage::getAutocomplete('%' . $ngram, $atoms, ($cfg->limit - count($suggestions)), $language);
    }
    foreach ($suggestions as $suggestion) {
      $json[] = [
        'value' => $suggestion,
        'label' => $suggestion,
      ];
    }
    return new JsonResponse($json);
  }

}
