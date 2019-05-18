<?php

namespace Drupal\content_synchronizer\Controller;

use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Service\ArchiveDownloader;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ArchiveDownloaderController.
 *
 * @package Drupal\content_synchronizer\Controller
 */
class ArchiveDownloaderController extends ControllerBase {

  /**
   * Download the tmp file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The download response.
   */
  public function downloadArchive(Request $request) {
    if ($request->query->has(ArchiveDownloader::ARCHIVE_PARAMS)) {
      $fileUri = ExportEntityWriter::GENERATOR_DIR . $request->query->get(ArchiveDownloader::ARCHIVE_PARAMS);

      if (file_exists($fileUri)) {
        $response = new Response(file_get_contents($fileUri));

        $disposition = $response->headers->makeDisposition(
          ResponseHeaderBag::DISPOSITION_ATTACHMENT,
          basename($fileUri)
        );
        $response->headers->set('Content-Disposition', $disposition);

        // Delete temporary file.
        $repName = substr($fileUri, 0, strrpos($fileUri, '/'));
        /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
        $fileSystem = \Drupal::service('file_system');
        $fileSystem->deleteRecursive($repName);

        return $response;
      }
    }
  }

}
