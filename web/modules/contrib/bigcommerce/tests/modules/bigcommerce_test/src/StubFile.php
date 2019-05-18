<?php

namespace Drupal\bigcommerce_test;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Small facade to create URLs for files used by stubs.
 */
class StubFile {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * StubFile constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Creates an absolute URL to file.
   *
   * @param string $filename
   *   The filename to create an absolute URL for.
   *
   * @return string
   *   Absolute URL to the file.
   */
  public function createUrl($filename) {
    $uri = $this->moduleHandler->getModule('bigcommerce_test')->getPath() . '/stubs/files/' . $filename;
    return file_create_url($uri);
  }

  /**
   * Helper function to write stub files.
   *
   * Add a call to this to \BigCommerce\Api\v3\ApiClient::callApi() after the
   * request is made. The best place is after the line:
   * @code
   * $http_body = substr($response, $http_header_size);
   * @endcode
   *
   * @param string $url
   *   The BigCommerce API URL being called.
   * @param string $method
   *   The HTTP verb.
   * @param string $body
   *   The HTTP response body.
   */
  public static function writeResponseToStub($url, $method, $body) {
    // Change the line below to change the directory where the output is
    // written.
    $directory = 'modules/contrib/bigcommerce/tests/modules/bigcommerce_test/stubs/products';
    // The first three parts of the path are not relevant.
    $filename_parts = array_slice(
      explode('/', trim(parse_url($url, PHP_URL_PATH), '/')),
      3
    );

    // Append the method if it is not GET to support methods other than get.
    $method = mb_strtolower($method);
    if ($method !== 'get') {
      $filename_parts[] = $method;
    }

    $filename = implode('_', $filename_parts) . '.json';
    file_put_contents($directory . '/' . $filename, $body);
  }

}
