<?php

namespace Drupal\uc_file\Controller;

use Drupal\Component\Utility\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Utility functions for autocompleting file download filenames.
 */
class FileAutocompleteController {

  /**
   * Returns autocompletion content for file name textfield.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function autocompleteFilename(Request $request) {
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      $filenames = db_select('uc_files', 'f')
        ->fields('f', ['filename'])
        ->condition('filename', '%' . db_like($typed_string) . '%', 'LIKE')
        ->execute();

      while ($name = $filenames->fetchField()) {
        $matches[] = ['value' => $name];
      }
    }

    return new JsonResponse($matches);
  }

}
