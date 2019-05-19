<?php

namespace Drupal\yandexdisk;

use Drupal\Component\Utility\UrlHelper;

/**
 * Yandex.Disk API WebDAV class.
 *
 * Here are only methods described in API docs. You probably want to use
 * YandexDiskApiWebdavHelper for extended functionality.
 *
 * @link http://api.yandex.com/disk/doc/dg/concepts/about.xml
 */
class YandexDiskApiWebdav {

  /**
   * Base path of a URI of the service api callback.
   */
  const SCHEMA_HOST = 'https://webdav.yandex.com';

  /**
   * Indicates that overwriting is allowed in copy/move methods.
   */
  const OVERWRITE_ALLOW = 'T';

  /**
   * Indicates that overwriting is not allowed in copy/move methods.
   */
  const OVERWRITE_DENY = 'F';

  /**
   * Disk path to work with.
   *
   * The $path must start with a slash.
   *
   * @var string
   */
  protected $path = '/';

  /**
   * Optional query parameters of the path to use with certain requests.
   *
   * @var array
   */
  protected $pathQuery;

  /**
   * Request method.
   *
   * @var string
   */
  protected $method;

  /**
   * Options that will be used in http request.
   *
   * @var array
   */
  protected $options;

  /**
   * Authorization header value.
   *
   * @var string
   */
  protected $authHeader;

  /**
   * Static field containing a result of last http request.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  public static $lastResponse;

  /**
   * Class constructor.
   *
   * @param string $auth_string
   *   Authentication type and token in string of form 'Type token'. For
   *   example: 'Basic abcdefghijklmnopqrstuvwxyz123456' or 'OAuth ...'.
   */
  public function __construct($auth_string) {
    $this->authHeader = $auth_string;
    $this->resetOptions();
  }

  /**
   * Resets options to initial state after request execution.
   */
  protected function resetOptions() {
    $this->options = [
      'timeout' => PHP_INT_MAX,
      'headers' => [
        'Accept' => '*/*',
        'Authorization' => $this->authHeader,
      ],
      'exceptions' => FALSE,
    ];
    $this->pathQuery = [];
  }

  /**
   * Sets the query parameters for the path.
   *
   * @param array $query
   *   The query parameter array to be set, e.g., $_GET.
   */
  public function setPathQuery(array $query) {
    $this->pathQuery = $query;
  }

  /**
   * Downloads a file.
   *
   * The start_byte and end_byte can be used for requesting a particular section
   * of the file. The response to this type of request contains the header
   * Content-Type: multipart/byteranges.
   *
   * @param string $path
   *   Path to the file.
   * @param int $start_byte
   *   (optional) An offset from the start of the file to get a file part.
   * @param int $end_byte
   *   (optional) An end byte is included in a file part that will be returned.
   *
   * @return $this
   *   Same object.
   */
  public function get($path, $start_byte = NULL, $end_byte = NULL) {
    $this->method = 'GET';
    $this->path = $path;

    if (isset($start_byte)) {
      $range = 'bytes=' . $start_byte;
      if (isset($end_byte)) {
        $range .= '-' . $end_byte;
      }

      $this->options['headers']['Range'] = $range;
    }

    return $this;
  }

  /**
   * Uploads a file.
   *
   * At the beginning and end of uploading the file, the service checks whether
   * the file exceeds the space available to the user on Disk. If there is not
   * enough space, the service returns a response with the code 507 Insufficient
   * Storage.
   * Support is provided for transferring compressed files (Content-Encoding:
   * gzip header) and chunked files (Transfer-Encoding: chunked).
   *
   * @param string $path
   *   Path to the file.
   * @param string $data
   *   Data to be saved to the file.
   * @param string $content_type
   *   (optional) Data type.
   *
   * @return $this
   *   Same object.
   */
  public function put($path, $data, $content_type = 'application/binary') {
    $this->method = 'PUT';
    $this->path = $path;

    $this->options['headers']['Content-Type'] = $content_type;

    if ($data !== '') {
      // Check for duplicate files.
      $this->options['headers']['Etag'] = md5($data);
      $this->options['headers']['Sha256'] = strtoupper(hash('sha256', $data));
      $this->options['headers']['Expect'] = '100-continue';

      // Compress data.
      if (extension_loaded('zlib') && strpos($content_type, 'text/') === 0) {
        $data_compressed = gzencode($data, 9, FORCE_GZIP);

        // Check if compressing worked.
        if (strlen($data_compressed) < strlen($data)) {
          $data = $data_compressed;
          $this->options['headers']['Content-Encoding'] = 'gzip';
        }
      }
    }

    $this->options['body'] = $data;

    return $this;
  }

  /**
   * Creates a directory.
   *
   * According to the protocol, only one directory can be created as the result
   * of a single request. If the application sends a request to create the
   * /a/b/c/ directory, but the /a/ directory does not contain a /b/ directory,
   * the service will not create the /b/ directory, and will respond with the
   * code 409 Conflict.
   *
   * @param string $path
   *   Path to the directory to create.
   *
   * @return $this
   *   Same object.
   */
  public function mkcol($path) {
    $this->method = 'MKCOL';
    $this->path = $path;

    return $this;
  }

  /**
   * Copies a file/directory.
   *
   * If parent directory where the file/directory should be copied to does not
   * exist, the service will respond with the code 409 Conflict.
   * If overwriting is not allowed and target exists, the service will respond
   * with the code 412 Precondition Failed.
   *
   * @param string $source
   *   Path to the file/directory to copy.
   * @param string $destination
   *   Path where the copy should be created.
   * @param string $overwrite
   *   (optional) Constant indicating whether overwriting is allowed or denied
   *   if target already exists. Default is to allow.
   *
   * @return $this
   *   Same object.
   */
  public function copy($source, $destination, $overwrite = self::OVERWRITE_ALLOW) {
    $this->method = 'COPY';
    $this->path = $source;

    $this->options['headers']['Destination'] = $destination;
    $this->options['headers']['Overwrite'] = $overwrite;

    return $this;
  }

  /**
   * Moves/renames a file/directory.
   *
   * If parent directory where the file/directory should be moved to does not
   * exist, the service will respond with the code 409 Conflict.
   * If overwriting is not allowed and target exists, the service will respond
   * with the code 412 Precondition Failed.
   *
   * @param string $source
   *   Path to the source file/directory.
   * @param string $destination
   *   New path for the file/directory.
   * @param string $overwrite
   *   (optional) Constant indicating whether overwriting is allowed or denied
   *   if target already exists. Default is to allow.
   *
   * @return $this
   *   Same object.
   */
  public function move($source, $destination, $overwrite = self::OVERWRITE_ALLOW) {
    $this->method = 'MOVE';
    $this->path = $source;

    $this->options['headers']['Destination'] = $destination;
    $this->options['headers']['Overwrite'] = $overwrite;

    return $this;
  }

  /**
   * Removes a file/directory.
   *
   * As specified in the protocol, removing a directory always removes all of
   * the files and directories that are in it.
   *
   * @param string $path
   *   Path to the file/directory to delete.
   *
   * @return $this
   *   Same object.
   */
  public function delete($path) {
    $this->method = 'DELETE';
    $this->path = $path;

    return $this;
  }

  /**
   * Gets file/directory properties.
   *
   * @param string $path
   *   Path to the file/directory.
   * @param int $depth
   *   (optional) Use 1 for directory to get a list of its contents properties.
   * @param string $data
   *   (optional) Data to send with the request.
   *
   * @return $this
   *   Same object.
   */
  public function propfind($path, $depth = 0, $data = NULL) {
    $this->method = 'PROPFIND';
    $this->path = $path;

    $this->options['headers']['Depth'] = $depth;

    $this->options['body'] = $data;

    return $this;
  }

  /**
   * Changes the properties of a file/directory.
   *
   * @param string $path
   *   Path to the file/directory.
   * @param string $data
   *   (optional) Data to send with the request.
   *
   * @return $this
   *   Same object.
   */
  public function proppatch($path, $data = NULL) {
    $this->method = 'PROPPATCH';
    $this->path = $path;

    $this->options['body'] = $data;

    return $this;
  }

  /**
   * Executes a request.
   *
   * @return bool
   *   Whether the request was executed successfully.
   *
   * @throws YandexDiskException
   *   If service failed to response.
   */
  public function execute() {
    $url = self::SCHEMA_HOST . UrlHelper::encodePath($this->path);
    if ($this->pathQuery) {
      $url .= '?' . UrlHelper::buildQuery($this->pathQuery);
    }
    $response = \Drupal::httpClient()->request($this->method, $url, $this->options);

    self::$lastResponse = $response;
    $code = (string) $response->getStatusCode();

    if (!$code) {
      throw new YandexDiskException(t('No response from service.'));
    }

    // Check for success code.
    $return = ($code[0] == 2 || $this->method == 'PUT' && $code == 100);

    // Prepare instance for future requests.
    $this->resetOptions();

    return $return;
  }

}
