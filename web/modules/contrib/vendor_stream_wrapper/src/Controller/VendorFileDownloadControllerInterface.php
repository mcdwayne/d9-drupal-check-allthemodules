<?php

namespace Drupal\vendor_stream_wrapper\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Vendor Stream Wrapper file controller interface.
 *
 * Sets up serving of files from the vendor directory, using the vendor://
 * stream wrapper.
 */
interface VendorFileDownloadControllerInterface {

  /**
   * Handles vendor file transfers.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the requested file does not exist.
   */
  public function download(Request $request);

}
