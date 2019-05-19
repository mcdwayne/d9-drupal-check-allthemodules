<?php

/**
 * @file
 * Contains \Drupal\lang\Controller\LanguageAutocompleteController.
 */

namespace Drupal\lang\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\lang\LanguageAutocomplete;

/**
 * Returns autocomplete responses for countries.
 */
class LanguageAutocompleteController implements ContainerInjectionInterface {

  /**
   * The user autocomplete helper class to find matching user names.
   *
   * @var \Drupal\lang\LanguageAutocomplete
   */
  protected $languageAutocomplete;

  /**
   * Constructs a LanguageAutocompleteController object.
   *
   * @param \Drupal\lang\LanguageAutocomplete $language_autocomplete
   *   The country autocomplete helper class to find matching country names.
   */
  public function __construct(LanguageAutocomplete $language_autocomplete) {
    $this->languageAutocomplete = $language_autocomplete;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lang.autocomplete')
    );
  }

  /**
   * Returns response for the language autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for languages.
   *
   * @see getMatches()
   */
  public function autocomplete(Request $request) {
    $matches = $this->languageAutocomplete->getMatches($request->query->get('q'));
    return new JsonResponse($matches);
  }
}
