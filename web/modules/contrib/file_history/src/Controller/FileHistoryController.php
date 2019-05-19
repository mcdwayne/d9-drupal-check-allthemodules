<?php

namespace Drupal\file_history\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class RememberFilesController.
 */
class FileHistoryController extends ControllerBase {

  /**
   * Download non public files.
   *
   * @param \Drupal\file\FileInterface $file
   *   File object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   File content
   */
  public function downloadFile(FileInterface $file) {

    $real_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $fileContent = file_get_contents($real_path);

    $response = new Response($fileContent);

    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename()
    );

    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
