<?php

namespace Drupal\file_encrypt\Controller;

use Drupal\file_encrypt\EncryptBinaryFileResponse;
use Drupal\system\FileDownloadController as CoreFileDownloadController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for encrypted file downloads.
 */
class FileDownloadController extends CoreFileDownloadController {

  /**
   * {@inheritdoc}
   */
  public function download(Request $request, $scheme = 'encrypt') {
    $response = parent::download($request, $scheme);
    return new EncryptBinaryFileResponse($response->getFile(), 200, $response->headers->all(), FALSE);
  }

}
