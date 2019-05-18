<?php

namespace Drupal\vendor_stream_wrapper\Service;

/**
 * Interfaces for the Vendor Stream Wrapper module services.
 */
interface VendorStreamWrapperServiceInterface {

  /**
   * Creates a public facing URL from URIs with the vendor:// schema.
   *
   * @param string $uri
   *   The vendor:// prefixed URI to be convereted to a public facing URL.
   *
   * @return string
   *   - If the $uri is prefixed with vendor://, and the path is valid, a public
   *     facing URL will be returned.
   *   - If the $uri is prefixed with vendor://, and the path is invalid, NULL
   *     is returned.
   *   - If $uri is not prefixed with vendor://, the passed $uri is returned
   *     unaltered.
   */
  public function creatUrlFromUri($uri);

}
