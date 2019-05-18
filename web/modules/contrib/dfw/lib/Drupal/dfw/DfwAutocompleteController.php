<?php

/**
 * @file
 * Contains \Drupal\dfw\DfwAutocompleteController.
 */
namespace Drupal\dfw;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for taxonomy user routes.
 */
class DfwAutocompleteController {

  /**
   * The user autocomplete helper class to find matching user names.
   *
   * @var \Drupal\dfw\DfwAutocomplete
   */
  protected $userAutocomplete;

  /**
   * Constructs an DfwAutocompleteController object.
   *
   * @param \Drupal\dfw\DfwAutocomplete $user_autocomplete
   *   The user autocomplete helper class to find matching user names.
   */
  public function __construct(DfwAutocomplete $user_autocomplete) {
    $this->userAutocomplete = $user_autocomplete;
  }

  /**
   * Returns response for the user autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param bool $include_anonymous
   *   (optional) TRUE if the the name used to indicate anonymous users (e.g.
   *   "Anonymous") should be autocompleted. Defaults to FALSE.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for existing users.
   *
   * @see \Drupal\dfw\DfwAutocomplete::getMatches()
   */
  public function autocompleteDfw(Request $request, $include_anonymous = FALSE) {
    $matches = $this->userAutocomplete->getMatches($request->query->get('q'), $include_anonymous);

    return new JsonResponse($matches);
  }

  /**
   * Returns response for the user autocompletion with the anonymous user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for existing users.
   *
   * @see \Drupal\dfw\UserRouteController\autocompleteDfw
   */
  public function autocompleteDfwAnonymous(Request $request) {
    return $this->autocompleteDfw($request, TRUE);
  }

}

