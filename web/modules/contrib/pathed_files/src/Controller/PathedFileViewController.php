<?php

namespace Drupal\pathed_file\Controller;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the pathed file's content to the client.
 */
class PathedFileViewController {

  /**
   * The HTTP response
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  public $response;

  /**
   * Prints the pathed file's content, and sets appropriate content-type header.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function content(EntityInterface $pathed_file) {
    $this->response = new Response();

    $this->setHeaders($pathed_file);
    $this->response->setContent($pathed_file->content);

    $this->response->sendHeaders();
    return $this->response;
  }

  /**
  * Sets headers for this response.
  *
  * @param object $pathed_file
  *   Pathed file entity passed from response.
  */
  private function setHeaders($pathed_file) {
    $this->response->headers->set('Content-Length', strlen($pathed_file->content));

    // Grab the file extension to determine content type.
    if (!preg_match('#\.(.*)$#', $pathed_file->path, $matches)) {
      return;
    }

    $extension = $matches[1];
    \Drupal::moduleHandler()->loadInclude('pathed_file', 'inc', 'includes/mime_types_map');
    $map = _pathed_files_get_mime_types();

    if (isset($map[$extension])) {
      $this->response->headers->set('Content-type', $map[$extension]);
    }
  }
}
