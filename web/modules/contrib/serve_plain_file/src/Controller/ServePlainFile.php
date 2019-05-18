<?php

namespace Drupal\serve_plain_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\serve_plain_file\Entity\ServedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to serve file content.
 */
class ServePlainFile extends ControllerBase {

  /**
   * Serves the content as text plain.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $id
   *   Config entity id (machine name).
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function content(Request $request, $id) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->entityTypeManager()->getStorage('served_file');
    /** @var \Drupal\serve_plain_file\Entity\ServedFileInterface $served_file */
    $served_file = $storage->load($id);

    if ($served_file && $request->getPathInfo() == "/" . $served_file->getPath()) {
      $response = new Response();
      $response->setContent($served_file->getContent());

      $allowed_mime_types = (array) $this->config('serve_plain_file.settings')->get('allowed_mime_types');
      $mime_type = $served_file->getMimeType();
      $mime_type = in_array($mime_type, $allowed_mime_types) ? $mime_type : ServedFile::DEFAULT_MIME_TYPE;
      $response->headers->set('Content-Type', $mime_type);

      $max_age = $served_file->getFileMaxAge();
      $response->setPublic();
      $response->setMaxAge($max_age);

      $expires = new \DateTime();
      $expires->setTimestamp(REQUEST_TIME + $max_age);
      $response->setExpires($expires);

      return $response;
    }

    throw new NotFoundHttpException();
  }

}
