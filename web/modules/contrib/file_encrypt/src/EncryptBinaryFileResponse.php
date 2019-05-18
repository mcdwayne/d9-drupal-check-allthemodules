<?php

namespace Drupal\file_encrypt;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class EncryptBinaryFileResponse extends BinaryFileResponse  {

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    parent::prepare($request);
    $this->fixContentLengthHeader();
    $this->setPrivate();
    return $this;
  }

  /**
   * Fixes the Content-Length response header.
   *
   * This works around a bug in symfony/http-foundation that causes
   * BinaryFileResponse to send the wrong Content-Length header value for files
   * modified by stream filters as encrypted files are. See
   * https://github.com/symfony/symfony/issues/19738.
   */
  protected function fixContentLengthHeader() {
    // The only way to get the size of the filtered data is to actually read it.
    ob_start();
    $size = readfile($this->getFile()->getPathname());
    ob_end_clean();

    $this->headers->set('Content-Length', $size);
  }

}
