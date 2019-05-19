<?php

namespace Drupal\yandexdisk;

/**
 * Yandex.Disk API WebDAV helper class.
 *
 * Each object of this class works with only one Yandex.Disk account.
 */
class YandexDiskApiWebdavHelper extends YandexDiskApiWebdav {

  /**
   * Yandex.Disk account name.
   *
   * @var string
   */
  public $user;

  /**
   * Static cache for resources properties.
   *
   * @var array
   *   Multiple-leveled associative array with the following structure:
   *   - Key is an account name, value is an array:
   *     - Key is a resource path, value is an array of properties or NULL in
   *       case resource does not exist. Possible resource's properties:
   *       - d:resourcetype: Empty element.
   *       - d:collection: Empty element, exists only in catalogue's properties.
   *       - d:getlastmodified: Time string ('Mon, 08 Oct 2012 07:02:36 GMT').
   *       - d:getetag: ETag string (only for file).
   *       - d:getcontenttype: Content-type string (only for file).
   *       - d:getcontentlength: Filesize in bytes (only for file).
   *       - d:displayname: Name of file/catalogue.
   *       - d:creationdate: Time string ('2012-10-08T07:02:36Z').
   *       - public_url: Web accessible URL for the resource.
   */
  protected static $propertiesCache;

  /**
   * Sets/removes a header option.
   *
   * @param string $name
   *   Header name.
   * @param mixed $value
   *   Header value to set. Set to NULL to remove header.
   */
  public function setHeader($name, $value) {
    if (isset($value)) {
      $this->options['headers'][$name] = $value;
    }
    else {
      unset($this->options['headers'][$name]);
    }
  }

  /**
   * Checks if operation is allowed for the user and if so executes the request.
   *
   * @return bool
   *   Whether the request was executed successfully.
   *
   * @throws YandexDiskException
   *   If user is not allowed to perform this operation.
   */
  public function execute() {
    $op = strtolower($this->method);
    $uri = 'yandexdisk://' . $this->getUser() . $this->path;
    if (!yandexdisk_access($op, $uri)) {
      throw new YandexDiskException(t('Access denied for current user to @op the @uri.', ['@op' => $op, '@uri' => $uri]));
    }

    // Clear the properties cache.
    if (!in_array($op, ['get', 'copy', 'propfind'])) {
      $cache = &$this->propertiesCache();
      unset($cache[$this->path]);
    }

    return parent::execute();
  }

  /**
   * Returns the account name of the current Disk instance.
   *
   * @return string
   *   Yandex.Disk account name.
   *
   * @throws YandexDiskException
   *   If request to the service failed.
   */
  public function getUser() {
    if (!isset($this->user)) {
      // Because this method can be called inside of other request execution,
      // save original options into variables to restore them after this
      // operation.
      $original_options = $this->options;
      $original_path = $this->path;
      $original_path_query = $this->pathQuery;
      $this->resetOptions();

      // This method is available only for OAuth authentication, and not for
      // Basic one.
      $this->get('/')->setPathQuery(['userinfo' => NULL]);

      // Avoid access checking in self::execute(), use parent.
      if (parent::execute()) {
        list($this->user) = sscanf((string) self::$lastResponse->getBody(), 'login:%s');
      }

      // Restore original options.
      $this->options = $original_options;
      $this->path = $original_path;
      $this->pathQuery = $original_path_query;

      if (!isset($this->user)) {
        throw new YandexDiskException(t('Cannot get the account name.'));
      }
    }

    return $this->user;
  }

  /**
   * Provides a static cache for resources properties of the current Disk.
   *
   * @return array
   *   An array of properties arrays for corresponding paths by reference.
   */
  protected function &propertiesCache() {
    if (!isset(self::$propertiesCache[$this->getUser()])) {
      self::$propertiesCache[$this->user] = [];
    }

    return self::$propertiesCache[$this->user];
  }

  /**
   * Retrieves resources properties from static cache or from the service.
   *
   * @param string $path
   *   Path of the resource.
   *
   * @return array|null
   *   Properties of the resource if one exists, NULL otherwise.
   *
   * @throws YandexDiskException
   *   If status code of response from the service is non-standard.
   */
  public function getProperties($path) {
    $properties = &$this->propertiesCache();

    if (!array_key_exists($path, $properties)) {
      $this->propfind($path)->execute();

      switch (self::$lastResponse->getStatusCode()) {
        case 200:
        case 207:
          $this->setProperties($path, (string) self::$lastResponse->getBody());
          break;

        case 404:
          $properties[$path] = NULL;
          break;

        default:
          throw new YandexDiskException();
      }
    }

    return $properties[$path];
  }

  /**
   * Parses resources properties from a service response and caches them.
   *
   * @param string $path
   *   Path of the resource.
   * @param string $raw_xml
   *   XML string as returned from a service.
   *
   * @return array
   *   Array of properties arrays for each path returned in XML.
   */
  public function setProperties($path, $raw_xml) {
    $properties = &$this->propertiesCache();
    $return = [];

    $xml = new \DOMDocument();
    $xml->loadXML($raw_xml);

    foreach ($xml->getElementsByTagName('response') as $i => $response) {
      $raw_properties = $response->getElementsByTagName('prop')->item(0);

      // Build an item's path.
      if ($i) {
        $item_name = $raw_properties
          ->getElementsByTagName('displayname')->item(0)->nodeValue;
        $item_path = rtrim($path, '/') . '/' . $item_name;
      }
      else {
        $item_path = $path;
      }

      foreach ($raw_properties->getElementsByTagName('*') as $property) {
        $properties[$item_path][$property->tagName] = trim($property->nodeValue);
      }

      $return[$item_path] = $properties[$item_path];
    }

    return $return;
  }

  /**
   * Checks whether path exists on Disk.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if path exists, FALSE otherwise.
   */
  public function pathExists($path) {
    return (bool) $this->getProperties($path);
  }

  /**
   * Checks if path on Disk is a regular file.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if the path exists and is a file, FALSE otherwise.
   */
  public function isFile($path) {
    if ($properties = $this->getProperties($path)) {
      return !isset($properties['d:collection']);
    }

    return FALSE;
  }

  /**
   * Checks if path on Disk is a directory.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if the path exists and is a directory, FALSE otherwise.
   */
  public function isDir($path) {
    if ($properties = $this->getProperties($path)) {
      return isset($properties['d:collection']);
    }

    return FALSE;
  }

  /**
   * Helper method to read from stream.
   *
   * @param string $path
   *   Path to the file.
   * @param int $offset
   *   An offset from the start of the file.
   * @param int $length
   *   A number of bytes to return.
   *
   * @return string
   *   Returns the extracted part of the file.
   *
   * @throws YandexDiskException
   *   If there was a problem to read the file.
   *
   * @see YandexDiskApiWebdav::get()
   */
  public function read($path, $offset, $length) {
    $this->get($path, $offset, $offset + $length - 1)->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 206:
        return (string) self::$lastResponse->getBody();

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Helper method to write to stream.
   *
   * @param string $path
   *   Path to the file.
   * @param string $data
   *   Data to be saved to the file.
   * @param string $content_type
   *   (optional) Data type.
   *
   * @return true
   *   If the file was created.
   *
   * @throws YandexDiskException
   *   If there was a problem to write the file.
   *
   * @see YandexDiskApiWebdav::put()
   */
  public function write($path, $data, $content_type = 'application/binary') {
    $this->put($path, $data, $content_type)->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 100:
      case 201:
        return TRUE;

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Retrieves a directory contents.
   *
   * Set offset and amount parameters to get a paginated list of elements. It is
   * assumed that the items are arranged alphabetically, and any nested
   * directories are listed before the files. The response shows the $amount
   * number of items without the requested directory itself.
   *
   * @param string $path
   *   Path to the directory.
   * @param int $offset
   *   (optional) Number of items to skip.
   * @param int $amount
   *   (optional) Desired number of items to return.
   *
   * @return string[]
   *   Array with names of directories and files on success.
   *
   * @throws YandexDiskException
   *   If there was a problem getting a directory contents, or if the $path is
   *   not a directory.
   */
  public function scanDir($path, $offset = 0, $amount = 0) {
    $this->propfind($path, 1);
    if ($amount) {
      $this->setPathQuery(['offset' => $offset, 'amount' => $amount]);
    }
    $this->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 207:
        $properties = $this->setProperties($path, (string) self::$lastResponse->getBody());

        if (!$this->isDir($path)) {
          throw new YandexDiskException(t('Resource is not a directory.'));
        }

        $list = [];

        foreach ($properties as $item_path => $item) {
          // Skip the requested directory itself.
          if ($item_path != $path) {
            $list[] = $item['d:displayname'];
          }
        }

        return $list;

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Helper method to get an image preview.
   *
   * @param string $path
   *   Path to the image.
   * @param string|int $size
   *   There are several ways to set the preview size:
   *   - T-shirt size. Supported values:
   *     - 'XXXS': 50 pixels on each side (square).
   *     - 'XXS': 75 pixels on each side (square).
   *     - 'XS': 100 pixels on each side (square).
   *     - 'S': 150 pixels wide, preserves original aspect ratio.
   *     - 'M': 300 pixels wide, preserves original aspect ratio.
   *     - 'L': 500 pixels wide, preserves original aspect ratio.
   *     - 'XL': 800 pixels wide, preserves original aspect ratio.
   *     - 'XXL': 1024 pixels wide, preserves original aspect ratio.
   *     - 'XXXL': 1280 pixels wide, preserves original aspect ratio.
   *   - An integer. Yandex.Disk returns a preview with this width. If the
   *     specified width is more than 100 pixels, the preview preserves the
   *     aspect ratio of the original image. Otherwise, the preview is
   *     additionally modified: the largest possible square section is taken
   *     from the center of the image to scale to the specified width.
   *   - Exact dimensions, such as '128x256'. Yandex.Disk returns a preview with
   *     the specified dimensions. The largest possible section with the
   *     specified width/height ratio is taken from the center of the original
   *     image (in the example, the ratio is 128/256 or 1/2). Then this section
   *     of the image is scaled to the specified dimensions.
   *   - Exact width or height, such as '128x' or 'x256'. Yandex.Disk returns
   *     a preview with the specified width or height that preserves the aspect
   *     ratio of the original image.
   *
   * @return string
   *   Binary image data on success.
   *
   * @throws YandexDiskException
   *   If there was a problem getting a preview.
   */
  public function imagePreview($path, $size) {
    $this->get($path)->setPathQuery(['preview' => NULL, 'size' => $size]);
    $this->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
        return (string) self::$lastResponse->getBody();

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Publishes file or directory.
   *
   * @param string $path
   *   Path to the file or directory.
   *
   * @return string
   *   Public URL on success.
   *
   * @throws YandexDiskException
   *   If there was a problem publishing the resource.
   */
  public function publish($path) {
    $xml = new \SimpleXMLElement('<propertyupdate/>');
    $xml['xmlns'] = 'DAV:';
    $xml->set->prop->public_url = 1;
    $xml->set->prop->public_url['xmlns'] = 'urn:yandex:disk:meta';

    $this->proppatch($path, $xml->asXML())->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 207:
        $properties = $this->setProperties($path, (string) self::$lastResponse->getBody());
        return $properties[$path]['public_url'];

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Unpublishes file or directory.
   *
   * @param string $path
   *   Path to the file or directory.
   *
   * @return bool
   *   TRUE on success.
   *
   * @throws YandexDiskException
   *   If there was a problem unpublishing the resource.
   */
  public function unpublish($path) {
    $xml = new \SimpleXMLElement('<propertyupdate/>');
    $xml['xmlns'] = 'DAV:';
    $xml->remove->prop->public_url['xmlns'] = 'urn:yandex:disk:meta';

    $this->proppatch($path, $xml->asXML())->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 207:
        $properties = $this->setProperties($path, (string) self::$lastResponse->getBody());
        return empty($properties[$path]['public_url']);

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Returns a public URL of the file or directory.
   *
   * @param string $path
   *   Path to the file or directory.
   *
   * @return string
   *   Public URL if there is one, or an empty string if resource is not
   *   published.
   *
   * @throws YandexDiskException
   *   If there was a problem checking the public URL.
   */
  public function publicUrl($path) {
    // 'Public_url' is not available with other properties. Propfind it now.
    $xml = new \SimpleXMLElement('<propfind/>');
    $xml['xmlns'] = 'DAV:';
    $xml->prop->public_url['xmlns'] = 'urn:yandex:disk:meta';

    $this->propfind($path, 0, $xml->asXML())->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 207:
        $properties = $this->setProperties($path, (string) self::$lastResponse->getBody());
        return $properties[$path]['public_url'];

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Returns an amount of free and/or used space on Disk in bytes.
   *
   * @param string $type
   *   (optional) Type of space amount to return:
   *   - 'used'.
   *   - 'available'.
   *
   * @return string|string[]
   *   If $type specified, then a number is returned. Otherwise, an array of
   *   numbers.
   *
   * @throws YandexDiskException
   *   If there was a problem checking an amount of Disk space.
   */
  public function quota($type = NULL) {
    $xml = new \SimpleXMLElement('<propfind/>');
    $xml['xmlns'] = 'DAV:';
    $prop = $xml->addChild('prop');
    $prop->addChild('quota-available-bytes');
    $prop->addChild('quota-used-bytes');

    $this->propfind('/', 0, $xml->asXML())->execute();

    switch (self::$lastResponse->getStatusCode()) {
      case 200:
      case 207:
        $properties = $this->setProperties('/', (string) self::$lastResponse->getBody());

        if ($type) {
          return $properties['/']['d:quota-' . $type . '-bytes'];
        }

        return $properties['/'];

      default:
        throw new YandexDiskException();
    }
  }

  /**
   * Creates a directory in Disk.
   *
   * @param string $path
   *   Path to the directory to be created.
   * @param bool $recursive
   *   (optional) Whether to create all the directories in the path recursively.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function mkdir($path, $recursive = TRUE) {
    if (!$recursive) {
      return $this->mkcol($path)->execute();
    }

    $levels = explode('/', trim($path, '/'));
    $count = count($levels);
    $success = FALSE;

    // Iterate from the deepest path to first found existing directory.
    for ($i = $count; $i > 0; $i--) {
      $path = '/' . implode('/', array_slice($levels, 0, $i)) . '/';
      if ($this->mkcol($path)->execute()) {
        $success = TRUE;
        break;
      }
    }

    if ($success) {
      // Now iterate from existing path and create each new directory.
      for (++$i; $i <= $count; $i++) {
        $path = '/' . implode('/', array_slice($levels, 0, $i)) . '/';
        if (!$this->mkcol($path)->execute()) {
          $success = FALSE;
          break;
        }
      }
    }

    return $success;
  }

  /**
   * Returns the MIME type of the file.
   *
   * @param string $path
   *   Path to the file.
   *
   * @return string|false
   *   String containing the MIME type of the resource if path is correct and is
   *   a file, FALSE otherwise.
   */
  public function getMimeType($path) {
    if ($this->isFile($path)) {
      $properties = $this->getProperties($path);
      return $properties['d:getcontenttype'];
    }

    return FALSE;
  }

}
