<?php

namespace Drupal\lupus_taxonomy_import\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the csv importer.
 */
class Import {

  /**
   * Gets an example file.
   *
   * @param string $type
   *   Example type, any filename of ./examples/*.csv without the `.csv`.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getExampleCsv($type) {
    $file = __DIR__ . '/examples/' . $type . '.csv';
    if (!is_readable($file)) {
      throw new NotFoundHttpException();
    }

    $response = new Response(file_get_contents($file));
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      "{$type}.csv"
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
