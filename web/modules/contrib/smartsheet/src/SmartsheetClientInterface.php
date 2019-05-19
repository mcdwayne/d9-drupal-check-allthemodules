<?php

namespace Drupal\smartsheet;

/**
 * Interface SmartsheetClientInterface.
 */
interface SmartsheetClientInterface {

  /**
   * Performs a GET request to the Smartsheet API.
   *
   * @param string $path
   *   The path of the request. Must begin with a /.
   * @param array $options
   *   (optional) An array of options for the request.
   *
   * @return array
   *   An array of metadata and data retrieved from Smartsheet.
   */
  public function get($path, array $options = []);

  /**
   * Performs a POST request to the Smartsheet API.
   *
   * @param string $path
   *   The path of the request. Must begin with a /.
   * @param array $options
   *   (optional) An array of options for the request.
   *
   * @return array
   *   An array of metadata and data retrieved from Smartsheet.
   */
  public function post($path, array $options = []);

  /**
   * Performs a PUT request to the Smartsheet API.
   *
   * @param string $path
   *   The path of the request. Must begin with a /.
   * @param array $options
   *   (optional) An array of options for the request.
   *
   * @return array
   *   An array of metadata and data retrieved from Smartsheet.
   */
  public function put($path, array $options = []);

  /**
   * Performs a DELETE request to the Smartsheet API.
   *
   * @param string $path
   *   The path of the request. Must begin with a /.
   * @param array $options
   *   (optional) An array of options for the request.
   *
   * @return array
   *   An array of metadata and data retrieved from Smartsheet.
   */
  public function delete($path, array $options = []);

}
