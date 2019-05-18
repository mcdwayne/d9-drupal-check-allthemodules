<?php

/**
 * @file
 * Contains \Drupal\commandbar\Controller\CommandbarAutocompleteController.
 */

namespace Drupal\commandbar\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\commandbar\CommandbarAutocomplete;

/**
 * Controller routines searching menu items and other searchable items.
 */
class CommandbarAutocompleteController implements ControllerInterface {

  /**
   * The commandbar autocomplete helper class to find matching menu items.
   *
   * @var \Drupal\commandbar\CommandbarAutocomplete
   */
  protected $commandbarAutocomplete;

  /**
   * Constructs a CommandbarAutocompleteController object.
   *
   * @param \Drupal\commandbar\CommandbarAutocomplete $commandbar_autocomplete
   *   The commandbar autocomplete helper class to find matching menu items.
   */
  public function __construct(CommandbarAutocomplete $commandbar_autocomplete) {
    $this->commandbarAutocomplete = $commandbar_autocomplete;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commandbar.autocomplete')
    );
  }

  /**
   * Returns response for commandbar autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for menu items.
   *
   * @see \Drupal\commandbar\CommandbarAutocomplete::getMatches()
   */
  public function autocompleteCommandbar(Request $request) {
    $matches = $this->commandbarAutocomplete->getMatches($request->query->get('q'));

    return new JsonResponse($matches);
  }

}