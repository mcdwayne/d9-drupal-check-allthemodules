<?php

namespace Drupal\gclient_storage\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\integro\Entity\Connector;

/**
 * Defines a google storage (gs://) stream wrapper class.
 */
class GclientStorageStream implements StreamWrapperInterface {

  /**
   * Configuration of the stream.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Gclient Storage Service.
   *
   * @var \Drupal\gclient_storage\GclientStorageServiceInterface
   */
  protected $service = NULL;

  /**
   * Stream connector.
   *
   * @var  \Drupal\integro\Entity\ConnectorInterface
   */
  protected $connector;

  /**
   * The opened protocol (e.g., "gs").
   *
   * @var string
   */
  private $protocol = 'gs';

  /**
   * Directory listing used by the dir_* methods.
   *
   * @var array
   */
  private $dir = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * The pointer to the next read or write
   *
   * @var int
   */
  protected $stream_pointer = 0;

  /**
   * A buffer for reading/wrting.
   *
   * @var string
   */
  protected $stream_data = NULL;

  /**
   * Data is not written to Google Cloud Storage in stream_write to minimize
   * requests. Instead, data is written to the $stream_data property.
   * This $write property is flagged as true, and in stream_flush, all the
   * data is written to Google Cloud Storage at once.
   *
   * @var <type> Boolean
   */
  protected $write = FALSE;

  /**
   * Indicates the current error state in the wrapper.
   *
   * @var bool
   */
  protected $errorState = FALSE;

  /**
   * GclientStorageStream constructor.
   */
  public function __construct() {
    // Since GclientStorageStream is always constructed with the same inputs (the
    // file URI is not part of construction), we store the constructed settings
    // statically. This is important for performance because the way Drupal's
    // APIs are used causes stream wrappers to be frequently re-constructed.
    $settings = &drupal_static('GclientStorageStream_settings');

    if ($settings !== NULL) {
      $this->config = $settings['config'];
      $this->connector = $settings['connector'];
      $this->service = $settings['service'];
      return;
    }

    // Retrieving settings.
    $config = \Drupal::config('gclient_storage.settings');
    foreach ($config->get() as $property => $value) {
      $this->config[$property] = $value;
    }

    // Setting up the connector.
    if (isset($this->config['integro_connector'])) {
      $this->connector = Connector::load($this->config['integro_connector']);
    }
    else {
      drupal_set_message(t('You need to set up connector for Google Storage integration. Otherwise it will not works properly.'), 'error');
    }

    // @todo Use dependency injection
    $this->service = \Drupal::service('gclient_storage');

    // Convert the signed URLs string to an associative array like
    // [blob => timeout].
    if (!empty($this->config['storage_signed_paths'])) {
      $storage_signed_paths = [];
      foreach (explode(PHP_EOL, $this->config['storage_signed_paths']) as $line) {
        $blob = trim($line);
        if ($blob) {
          if (preg_match('/(.*)\|(.*)/', $blob, $matches)) {
            $blob = $matches[2];
            $timeout = $matches[1];
            $storage_signed_paths[$blob] = $timeout;
          }
          else {
            $storage_signed_paths[$blob] = 60;
          }
        }
      }
      $this->config['storage_signed_paths'] = $storage_signed_paths;
    }
    else {
      $this->config['storage_signed_paths'] = [];
    }

    // Convert the force download paths to an array.
    if (!empty($this->config['storage_download_paths'])) {
      $storage_download_paths = [];
      foreach (explode(PHP_EOL, $this->config['storage_download_paths']) as $line) {
        $blob = trim($line);
        if ($blob) {
          $storage_download_paths[] = $blob;
        }
      }
      $this->config['storage_download_paths'] = $storage_download_paths;
    }
    else {
      $this->config['storage_download_paths'] = [];
    }

    // Save all the work we just did, so that subsequent GclientStorageStream
    // constructions don't have to repeat it.
    $settings['config'] = $this->config;
    $settings['service'] = $this->service;
    $settings['connector'] = $this->connector;
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Google Storage');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Google Storage bucket for serving files.');
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   *
   * Returns a web accessible URL for the resource.
   *
   * The format of the returned URL will be different depending on how the Google Storage
   * integration has been configured on the GClient Storage admin page.
   *
   * @return string
   *   A web accessible URL for the resource.
   */
  public function getExternalUrl() {
    // In case we're on Windows, replace backslashes with forward-slashes.
    // Note that $uri is the unaltered value of the File's URI, while
    // $uri_key may be changed at various points to account for implementation
    // details on the storage side (e.g. storage_root, etc).
    $uri_key = str_replace('\\', '/', file_uri_target($this->uri));

    // If this is a private:// file, it must be served through the
    // system/files/$path URL, which allows Drupal to restrict access
    // based on who's logged in.
    if (\Drupal::service('file_system')->uriScheme($this->uri) == 'private') {
      return Url::fromRoute('system.private_file_download', ['filepath' => $uri_key], ['absolute' => TRUE])
        ->toString();
    }

    // When generating an image derivative URL, e.g. styles/thumbnail/blah.jpg,
    // if the file doesn't exist, provide an URL to gclient storage's special version of
    // image_style_deliver(), which will create the derivative when that URL
    // gets requested.
    $path_parts = explode('/', $uri_key);
    if ($path_parts[0] == 'styles' && substr($uri_key, -4) != '.css') {
      if (!$this->getObject($this->uri)) {
        // The style delivery path looks like: gs/files/styles/thumbnail/...
        // And $path_parts looks like ['styles', 'thumbnail', ...],
        // so just prepend gs/files/.
        array_unshift($path_parts, $this->protocol, 'files');
        $path = implode('/', $path_parts);
        return $GLOBALS['base_url'] . '/' . UrlHelper::encodePath($path);
      }
    }

    // Deal with public:// files.
    if (\Drupal::service('file_system')->uriScheme($this->uri) == 'public') {
      // Rewrite all css/js file paths unless the user has told us not to.
      if ($this->config['stream_rewrite_cssjs']) {
        if (substr($uri_key, -4) == '.css') {
          // Send requests for public CSS files to /gstorage-css/path/to/file.css.
          // Users must set that path up in the webserver config as a proxy into
          // their Google Storage bucket's gstorage-public/ folder.
          return "{$GLOBALS['base_url']}/gstorage-css/" . UrlHelper::encodePath($uri_key);
        }
        else {
          if (substr($uri_key, -3) == '.js') {
            // Send requests for public JS files to /gstorage-js/path/to/file.js.
            // Like with CSS, the user must set up that path as a proxy.
            return "{$GLOBALS['base_url']}/gstorage-js/" . UrlHelper::encodePath($uri_key);
          }
        }
      }

      // public:// files are stored in storage inside the public folder.
      $public_folder = !empty($this->config['stream_public_folder']) ? $this->config['stream_public_folder'] : 'gstorage-public';
      $uri_key = "{$public_folder}/$uri_key";
    }

    // Set up the URL settings as specified in our settings page.
    $url_settings = [
      'signed_url' => FALSE,
      'force_download' => FALSE,
      'timeout' => 60,
//      'api_args' => ['Scheme' => !empty($this->config['use_https']) ? 'https' : 'http'],
      'api_args' => ['Scheme' => 'https'],
      'custom_GET_args' => [],
    ];

    // Signed URLs.
    foreach ($this->config['storage_signed_paths'] as $blob => $timeout) {
      // ^ is used as the delimeter because it's an illegal character in URLs.
      if (preg_match("^$blob^", $uri_key)) {
        $url_settings['signed_url'] = TRUE;
        $url_settings['timeout'] = $timeout;
        break;
      }
    }
    // Force Download.
    foreach ($this->config['storage_download_paths'] as $blob) {
      if (preg_match("^$blob^", $uri_key)) {
        $filename = basename($uri_key);
        $url_settings['api_args']['ResponseContentDisposition'] = "attachment; filename=\"$filename\"";
        $url_settings['force_download'] = TRUE;
        break;
      }
    }

    // Allow other modules to change the URL settings.
    \Drupal::moduleHandler()->alter('gclient_storage_url_settings', $url_settings, $uri_key);

    // If a root folder has been set, prepend it to the $uri_key at this time.
    if (!empty($this->config['storage_root'])) {
      $uri_key = "{$this->config['storage_root']}/$uri_key";
    }

    $metadata = $this->readCache($this->uri);

//    if (empty($this->config['cname'])) {
//      // We're not using a CNAME, so we ask Google Storage for the URL.
      if ($url_settings['signed_url']) {
        $external_url = $metadata['url'];
      }
      else {
        $external_url = $metadata['url'];
      }
//    }
//    else {
//      // We are using a CNAME, so we need to manually construct the URL.
//      $external_url = rtrim($this->config['cname_domain'], '/') . '/' . UrlHelper::encodePath($uri_key);
//    }

    // If this file is versioned, append the version number as a GET arg to
    // ensure that browser caches will be bypassed upon version changes.
    if (!empty($metadata['version'])) {
      $external_url = $this->appendGetArg($external_url, 'generation', $metadata['version']);
    }

    // If another module added a 'custom_GET_args' array to the url settings,
    // process it here.
    if (!empty($url_settings['custom_GET_args'])) {
      foreach ($url_settings['custom_GET_args'] as $name => $value) {
        $external_url = $this->appendGetArg($external_url, $name, $value);
      }
    }

    return $external_url;
  }

  /**
   * Helper function to safely append a GET argument to a given base URL.
   *
   * @param string $base_url
   *   The URL onto which the GET arg will be appended.
   * @param string $name
   *   The name of the GET argument.
   * @param string $value
   *   The value of the GET argument. Optional.
   *
   * @return string
   *   The converted path GET argument.
   */
  protected static function appendGetArg($base_url, $name, $value = NULL) {
    $separator = strpos($base_url, '?') === FALSE ? '?' : '&';
    $new_url = "{$base_url}{$separator}{$name}";
    if ($value !== NULL) {
      $new_url .= "=$value";
    }
    return $new_url;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support realpath().
   *
   * @return bool
   *   Always returns FALSE.
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Gets the name of the parent directory of a given path.
   *
   * @param string $uri
   *   An optional URI.
   *
   * @return string
   *   The directory name, or FALSE if not applicable.
   *
   * @see \Drupal::service('file_system')->dirname()
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $scheme = \Drupal::service('file_system')->uriScheme($uri);
    $dirname = dirname(file_uri_target($uri));

    // When the dirname() call above is given '$scheme://', it returns '.'.
    // But '$scheme://.' is an invalid uri, so we return "$scheme://" instead.
    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }

  /**
   * {@inheritdoc}
   *
   * Support for fopen(), file_get_contents(), file_put_contents() etc.
   *
   * @param string $uri
   *   The URI of the file to open.
   * @param string $mode
   *   The file mode. Only 'r', 'w', 'a', and 'x' are supported.
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string $opened_path
   *   An OUT parameter populated with the path which was opened.
   *   This wrapper does not support this parameter.
   *
   * @return bool
   *   TRUE if file was opened successfully. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->setUri($uri);

    $this->stream_pointer = 0;
    $this->stream_data = NULL;

    $result = $this->connector->getClient()->auth($this->connector->getClientConfiguration());
    if (isset($result['authorized']) && $result['authorized'] === TRUE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support flock().
   *
   * @return bool
   *   Always Returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    return FALSE;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param int $count
   *   Maximum number of bytes to be read.
   *
   * @return string|bool
   *   The string that was read, or FALSE in case of an error.
   *
   * @see http://php.net/manual/streamwrapper.stream-read.php
   */
  public function stream_read($count) {
    if (!$this->stream_data) {
      $this->stream_data = $this->readObjectBody($this->getUri());
    }

    $data = substr($this->stream_data, $this->stream_pointer, $count);
    $this->stream_pointer += $count;
    return $data;
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * @param string $data
   *   The string to be written.
   *
   * @return int
   *   The number of bytes written.
   *
   * @see http://php.net/manual/streamwrapper.stream-write.php
   */
  public function stream_write($data) {
    // Set up "write" flag.
    $this->write = TRUE;

    $this->stream_data .= $data;
    $bytes_written = strlen($data);
    $this->stream_pointer += $bytes_written;

    return $bytes_written;
  }

  /**
   * Support for feof().
   *
   * @return bool
   *   TRUE if end-of-file has been reached.
   *
   * @see http://php.net/manual/streamwrapper.stream-eof.php
   */
  public function stream_eof() {
    if (!$this->stream_data) {
      $this->stream_data = $this->readObjectBody($this->getUri());
    }
    return $this->stream_pointer >= strlen($this->stream_data);
  }

  /**
   * {@inheritdoc}
   * @todo mvv needs to be refactored
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    // fseek returns 0 on success and -1 on a failure.
    // stream_seek   1 on success and  0 on a failure.
    return !fseek($this->handle, $offset, $whence);
  }

  /**
   * {@inheritdoc}
   *
   * Support for fflush(). Flush current cached stream data.
   *
   * @return bool
   *   returns always TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-flush.php
   */
  public function stream_flush() {
    if ($this->write) {

      $parameters = $this->prepareParameters($this->getUri(), 'insert', ['data' => $this->stream_data]);

      try {
        $result = $this->connector->getIntegration()->operation($this->connector, 'objects.insert', $parameters)->execute();
      }
      catch (\Exception $e) {
        watchdog_exception('gclient_storage', $e);
        return $this->triggerError($e->getMessage());
      }

      $this->writeUriToCache($this->getUri(), $result);
      // return isset($result->size) ? $result->size : 0;
    }

    $this->stream_data = NULL;
    $this->stream_pointer = 0;
    return TRUE;
  }

  /**
   * Support for ftell().
   *
   * @return bool
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/streamwrapper.stream-tell.php
   */
  public function stream_tell() {
    return $this->stream_pointer;
  }

  /**
   * Support for fstat().
   *
   * @return bool
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/streamwrapper.stream-stat.php
   */
  public function stream_stat() {
    return [
      'size' => strlen($this->stream_data),
    ];
  }

  /**
   * Support for fclose().
   *
   * @return bool
   *   returns always TRUE.
   *
   * @see http://php.net/manual/streamwrapper.stream-close.php
   */
  public function stream_close() {
    unset($this->stream_data);
    $this->stream_data = NULL;
    $this->stream_pointer = 0;
    $this->write = FALSE;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support touch(), chmod(), chown(), or chgrp().
   *
   * Manual recommends return FALSE for not implemented options, but Drupal
   * require TRUE in some cases like chmod for avoid watchdog erros.
   *
   * Returns FALSE if the option is not included in bypassed_options array
   * otherwise, TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-metadata.php
   * @see \Drupal\Core\File\FileSystem::chmod()
   */
  public function stream_metadata($uri, $option, $value) {
    $bypassed_options = [STREAM_META_ACCESS];
    return in_array($option, $bypassed_options);
  }

  /**
   * {@inheritdoc}
   *
   * Since Windows systems do not allow it and it is not needed for most use
   * cases anyway, this method is not supported on local files and will trigger
   * an error and return false. If needed, custom subclasses can provide
   * OS-specific implementations for advanced use cases.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    trigger_error('stream_set_option() not supported for local file based stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support stream_truncate.
   *
   * Always returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-truncate.php
   */
  public function stream_truncate($new_size) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for unlink().
   *
   * @param string $uri
   *   The uri of the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted, regardless of whether or not
   *   the file actually existed.
   *   FALSE if the call to Google Storage failed, in which case the file will not be
   *   removed from the cache.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $this->setUri($uri);
    $result = $this->deleteObject($uri);
    if ($result && $result->getStatusCode() == '204') {
      $this->deleteCache($uri);
      clearstatcache(TRUE, $uri);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for rename().
   *
   * If $to_uri exists, this file will be overwritten. This behavior is
   * identical to the PHP rename() function.
   *
   * @param string $from_uri
   *   The uri of the file to be renamed.
   * @param string $to_uri
   *   The new uri for the file.
   *
   * @return bool
   *   TRUE if file was successfully renamed. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for mkdir().
   *
   * @param string $uri
   *   The URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   TRUE if the directory was successfully created. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    // Some Drupal plugins call mkdir with a trailing slash. We mustn't store
    // that slash in the cache.
    $uri = rtrim($uri, '/');

    clearstatcache(TRUE, $uri);
    // If this URI already exists in the cache, return TRUE if it's a folder
    // (so that recursive calls won't improperly report failure when they
    // reach an existing ancestor), or FALSE if it's a file (failure).
    $test_metadata = $this->readCache($uri);
    if ($test_metadata) {
      return (bool) $test_metadata['dir'];
    }

    $metadata = $this->service->convertMetadata($uri, []);
    $this->writeCache($metadata);

    // If the STREAM_MKDIR_RECURSIVE option was specified, also create all the
    // ancestor folders of this uri, except for the root directory.
    $parent_dir = \Drupal::service('file_system')->dirname($uri);
    if (($options & STREAM_MKDIR_RECURSIVE) && file_uri_target($parent_dir) != '') {
      return $this->mkdir($parent_dir, $mode, $options);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for rmdir().
   *
   * @param string $uri
   *   The URI to the folder to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   TRUE if folder is successfully removed.
   *   FALSE if $uri isn't a folder, or the folder is not empty.
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    if (!$this->isDir($uri)) {
      return FALSE;
    }

    // We need a version of $uri with no / because folders are cached with no /.
    // We also need one with the /, because it might be a file in Google Storage that
    // ends with /. In addition, we must differentiate against files with this
    // folder's name as a substring.
    // e.g. rmdir('gs://foo/bar') should ignore gs://foo/barbell.jpg.
    $base_path = rtrim($uri, '/');
    $slash_path = $base_path . '/';

    // Check if the folder is empty.
    $query = \Drupal::database()->select(GCLIENT_STORAGE_OBJECTS, 'objects');
    $query->fields('objects')
      ->condition('uri', $query->escapeLike($slash_path) . '%', 'LIKE');

    $file_count = $query->countQuery()->execute()->fetchField();

    // If the folder is empty, it's eligible for deletion.
    if ($file_count == 0) {
      $this->deleteCache($uri);
      clearstatcache(TRUE, $uri);
      return TRUE;
    }

    // The folder is non-empty.
    return FALSE;
  }

  /**
   * Support for stat().
   *
   * @param string $uri
   *   A string containing the URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *
   * @return array
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/streamwrapper.url-stat.php
   */
  public function url_stat($uri, $flags) {
    $this->setUri($uri);
    return $this->stat($uri);
  }

  /**
   * Determine whether the $uri is a directory.
   *
   * @param string $uri
   *   The path of the resource to check.
   *
   * @return bool
   *   TRUE if the resource is a directory.
   */
  protected function isDir($uri) {
    $metadata = $this->getObject($uri);
    return $metadata ? $metadata['dir'] : FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for opendir().
   *
   * @param string $uri
   *   The URI to the directory to open.
   * @param int $options
   *   A flag used to enable safe_mode.
   *   This wrapper doesn't support safe_mode, so this parameter is ignored.
   *
   * @return bool
   *   TRUE on success. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options = NULL) {
    if (!$this->isDir($uri)) {
      return FALSE;
    }

    $scheme = \Drupal::service('file_system')->uriScheme($uri);
    $base_path = rtrim($uri, '/');
    $slash_path = $base_path . '/';

    // If this path was originally a root folder (e.g. gs://), the above code
    // removed *both* slashes but only added one back. So we need to add
    // back the second slash.
    if ($slash_path == "$scheme:/") {
      $slash_path = "$scheme://";
    }

    // Get the list of paths for files and folders which are children of the
    // specified folder, but not grandchildren.
    $query = \Drupal::database()->select(GCLIENT_STORAGE_OBJECTS, 'objects');
    $query->fields('objects', ['uri']);
    $query->condition('uri', $query->escapeLike($slash_path) . '%', 'LIKE');
    $query->condition('uri', $query->escapeLike($slash_path) . '%/%', 'NOT LIKE');
    $child_paths = $query->execute()->fetchCol(0);

    $this->dir = [];
    foreach ($child_paths as $child_path) {
      $this->dir[] = basename($child_path);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Support for readdir().
   *
   * @return string
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   * @todo needs to be refactored
   * @todo needs to be tested
   */
  public function dir_readdir() {
    $entry = each($this->dir);
    return $entry ? $entry['value'] : FALSE;
  }

  /**
   * Support for rewinddir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir() {
    return TRUE;
  }

  /**
   * Support for closedir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    return TRUE;
  }

  /**
   * Gets the path that the wrapper is responsible for.
   *
   * This function isn't part of DrupalStreamWrapperInterface, but the rest
   * of Drupal calls it as if it were, so we need to define it.
   *
   * @return string
   *   The empty string. Since this is a remote stream wrapper,
   *   it has no directory path.
   *
   * @see \Drupal\Core\File\LocalStream::getDirectoryPath()
   */
  public function getDirectoryPath() {
    return '';
  }

  /**
   * Get the status of the file with the specified URI.
   *
   * Implementation of a stat method to ensure that remote files don't fail
   * checks when they should pass.
   *
   * @param string $uri
   *   The uri of the resource.
   *
   * @return array|bool
   *   An array with file status, or FALSE if the file doesn't exist.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  protected function stat($uri) {
    $metadata = $this->getObject($uri);

    if ($metadata) {
      $stat = [];
      $stat[0] = $stat['dev'] = 0;
      $stat[1] = $stat['ino'] = 0;
      // Use the S_IFDIR posix flag for directories, S_IFREG for files.
      // All files are considered writable, so OR in 0777.
      $stat[2] = $stat['mode'] = ($metadata['dir'] ? 0040000 : 0100000) | 0777;
      $stat[3] = $stat['nlink'] = 0;
      $stat[4] = $stat['uid'] = 0;
      $stat[5] = $stat['gid'] = 0;
      $stat[6] = $stat['rdev'] = 0;
      $stat[7] = $stat['size'] = 0;
      $stat[8] = $stat['atime'] = 0;
      $stat[9] = $stat['mtime'] = 0;
      $stat[10] = $stat['ctime'] = 0;
      $stat[11] = $stat['blksize'] = 0;
      $stat[12] = $stat['blocks'] = 0;

      if (!$metadata['dir']) {
        $stat[4] = $stat['uid'] = 'gstorage';
        $stat[7] = $stat['size'] = $metadata['filesize'];
        $stat[8] = $stat['atime'] = $metadata['timestamp'];
        $stat[9] = $stat['mtime'] = $metadata['timestamp'];
        $stat[10] = $stat['ctime'] = $metadata['timestamp'];
      }
      return $stat;
    }
    return FALSE;
  }

  /**
   * Prepares parameters for a command.
   *
   * @param string $uri
   *   Uri to the required object.
   * @param string $command
   *
   * @return array A parameters array.
   *   A parameters array.
   */
  protected function prepareParameters($uri, $command = 'get', $args = []) {
    $file_path = $this->getUriStorage($uri);
    $bucket = $this->connector->getClient()->getConfiguration()['bucket'];

    $parameters = [
      'bucket' => $bucket,
    ];

    if ($command == 'get') {
      $object = $file_path;
      $parameters['object'] = $object;

      $optParams = [
      ];
      $parameters['optParams'] = $optParams;
    }

    if ($command == 'insert') {
      $finfo = new \finfo(FILEINFO_MIME_TYPE);
      $content_type = $finfo->buffer($args['data']);

      $postBody = new \Google_Service_Storage_StorageObject();
      $postBody->setName($file_path);
      $postBody->setBucket($bucket);
      $postBody->setContentType($content_type);
      $parameters['postBody'] = $postBody;

      $optParams = [
        'name' => $file_path,
        'data' => $args['data'],
        'uploadType' => 'media',
        'mimeType' => $content_type,
      ];
      $parameters['optParams'] = $optParams;
    }

    if ($command == 'delete') {
      $object = $file_path;
      $parameters['object'] = $object;

      $optParams = [
      ];
      $parameters['optParams'] = $optParams;
    }

    return $parameters;
  }

  /**
   * Converts a Drupal URI path into what is expected to be stored in Google Storage.
   *
   * @param string $uri
   *   An appropriate URI formatted like 'protocol://path'.
   *
   * @return string
   *   A converted string ready for storage to process it.
   */
  protected function getUriStorage($uri) {
    // Remove the protocol.
    list($scheme, $path) = explode('://', $uri);

    if (!empty($path)) {
      // public:// file are all placed in the stream_public_folder.
      $public_folder = !empty($this->config['stream_public_folder']) ? $this->config['stream_public_folder'] : 'gstorage-public';
      $private_folder = !empty($this->config['stream_private_folder']) ? $this->config['stream_private_folder'] : 'gstorage-private';
      if (\Drupal::service('file_system')->uriScheme($uri) == 'public') {
        $path = "$public_folder/{$path}";
      }
      // private:// file are all placed in the stream_private_folder.
      elseif (\Drupal::service('file_system')->uriScheme($uri) == 'private') {
        $path = "$private_folder/{$path}";
      }

      // If it's set, all files are placed in the root folder.
      if (!empty($this->config['storage_root'])) {
        $path = "{$this->config['storage_root']}/{$path}";
      }
    }

    return $path;
  }

  /**
   * Try to fetch an object from the metadata cache.
   *
   * If that file or directory isn't in the cache, we assume it doesn't exist.
   *
   * @param string $uri
   *   The uri of the resource to check.
   *
   * @return array|bool
   *   An array if the $uri exists, otherwise FALSE.
   */
  protected function getObject($uri) {
    // For the root directory, return metadata for a generic folder.
    if (file_uri_target($uri) == '') {
      return $this->service->convertMetadata('/', []);
    }

    // Trim any trailing '/', in case this is a folder request.
    $uri = rtrim($uri, '/');

    // Check if this URI is in the cache.
    $metadata = $this->readCache($uri);

    // If cache ignore is enabled, query Google Storage for all URIs which aren't in the
    // cache, and non-folder URIs which are.
    if (!$this->config['metadata_cache'] && !$metadata['dir']) {
      try {
        // If getMetadata() returns FALSE, the file doesn't exist.
        $metadata = $this->getMetadata($uri);
      }
      catch (\Exception $e) {
        return $this->triggerError($e->getMessage());
      }
    }

    return $metadata;
  }

  /**
   * Returns the converted metadata for an object in Google Storage.
   *
   * @param string $uri
   *   The URI for the object in storage.
   *
   * @return array
   *   An array of DB-compatible file metadata.
   *
   * @throws \Exception
   */
  protected function getMetadata($uri) {
    return $this->service->convertMetadata($uri, $this->readObject($uri));
  }

  /**
   * Reads an object from Google Storage.
   *
   * @param string $uri
   *   The URI for the object in storage.
   *
   * @return mixed
   *
   * @throws \Exception
   */
  protected function readObject($uri) {
    $parameters = $this->prepareParameters($uri, 'get');
    try {
      $result = $this->connector->getIntegration()->operation($this->connector, 'objects.get', $parameters)->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('gclient_storage', $e);
      return $this->triggerError($e->getMessage());
    }

    return $result;
  }

  /**
   * Deletes an object from Google Storage.
   *
   * @param string $uri
   *   The URI for the object in storage.
   *
   * @return mixed
   *
   * @throws \Exception
   */
  protected function deleteObject($uri) {
    $parameters = $this->prepareParameters($uri, 'delete');
    try {
      $result = $this->connector->getIntegration()->operation($this->connector, 'objects.delete', $parameters)->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('gclient_storage', $e);
      return $this->triggerError($e->getMessage());
    }

    return $result;
  }

  /**
   * Reads an object body from Google Storage.
   *
   * @param string $uri
   *   The URI for the object in storage.
   *
   * @return mixed
   *
   * @throws \Exception
   */
  protected function readObjectBody($uri) {
    $object = $this->readObject($uri);
    $client = $this->connector->getClient()->auth($this->connector->getClientConfiguration())['client'];

    // create an authorized HTTP client
    $httpClient = $client->authorize();
    $response = $httpClient->request('GET', $object->getMediaLink());

    return $this->stream_data = $response->getBody();
  }

  /**
   * Fetch an object from the file metadata cache table.
   *
   * @param string $uri
   *   The uri of the resource to check.
   *
   * @return array
   *   An array of metadata if the $uri is in the cache. Otherwise, FALSE.
   */
  protected function readCache($uri) {
    $uri = file_stream_wrapper_uri_normalize($uri);

    // Cache DB reads so that faster caching mechanisms (e.g. redis, memcache)
    // can further improve performance.
    $cid = GCLIENT_STORAGE_CACHE_PREFIX . $uri;
    $cache = \Drupal::cache(GCLIENT_STORAGE_CACHE_BIN);

    if ($cached = $cache->get($cid)) {
      $record = $cached->data;
    }
    else {
      $lock = \Drupal::lock();
      // Cache miss. Avoid a stampede.
      if (!$lock->acquire($cid, 1)) {
        // Another request is building the variable cache. Wait, then re-run
        // this function.
        $lock->wait($cid);
        $record = $this->readCache($uri);
      }
      else {
        $record = \Drupal::database()->select(GCLIENT_STORAGE_OBJECTS, 'objects')
          ->fields('objects')
          ->condition('uri', $uri, '=')
          ->execute()
          ->fetchAssoc();

        if ($record) {
          $cache->set($cid, $record, Cache::PERMANENT, [GCLIENT_STORAGE_CACHE_TAG]);
        }
        $lock->release($cid);
      }
    }

    return $record ? $record : FALSE;
  }

  /**
   * Write an object's (and its ancestor folders') metadata to the cache.
   *
   * @param array $metadata
   *   An associative array of file metadata in this format:
   *     'uri' => The full URI of the file, including the scheme.
   *     'version' => The version of the file.
   *     'filemime' => MIME file type.
   *     'filesize' => The size of the file, in bytes.
   *     'timestamp' => The file's create/update timestamp.
   *     'dir' => A boolean indicating whether the object is a directory.
   *
   * @throws \Exception
   *   Exceptions which occur in the database call will percolate.
   */
  protected function writeCache(array $metadata) {
    $metadata['uri'] = file_stream_wrapper_uri_normalize($metadata['uri']);

    \Drupal::database()->merge(GCLIENT_STORAGE_OBJECTS)
      ->key(['uri' => $metadata['uri']])
      ->fields($metadata)
      ->execute();

    // Clear this URI from the Drupal cache, to ensure the next read isn't
    // from a stale cache entry.
    $cid = GCLIENT_STORAGE_CACHE_PREFIX . $metadata['uri'];
    $cache = \Drupal::cache(GCLIENT_STORAGE_CACHE_BIN);
    $cache->delete($cid);

    $dirname = \Drupal::service('file_system')->dirname($metadata['uri']);
    // If this file isn't in the root directory, also write this file's
    // ancestor folders to the cache.
    if (file_uri_target($dirname) != '') {
      $this->mkdir($dirname, NULL, STREAM_MKDIR_RECURSIVE);
    }
  }

  /**
   * Write the file at the given URI into the metadata cache.
   *
   * @param string $uri
   * @param mixed $result
   */
  protected function writeUriToCache($uri, $result) {
    $metadata = $this->service->convertMetadata($uri, $result);
    $this->writeCache($metadata);
    clearstatcache(TRUE, $uri);
  }

  /**
   * Deletes an object's metadata from the cache.
   *
   * @param mixed $uri
   *   A string (or array of strings) containing the URI(s) of the object(s)
   *   to be deleted.
   *
   * @return int
   *
   * @throws \Exception
   *   Exceptions which occur in the database call will percolate.
   */
  protected function deleteCache($uri) {
    if (!is_array($uri)) {
      $uri = [$uri];
    }

    $cids = [];

    // Build an OR query to delete all the URIs at once.
    $delete_query = \Drupal::database()->delete(GCLIENT_STORAGE_OBJECTS);
    $or = $delete_query->orConditionGroup();
    foreach ($uri as $u) {
      $or->condition('uri', $u, '=');
      // Add URI to cids to be cleared from the Drupal cache.
      $cids[] = GCLIENT_STORAGE_CACHE_PREFIX . $u;
    }

    // Clear URIs from the Drupal cache.
    $cache = \Drupal::cache(GCLIENT_STORAGE_CACHE_BIN);
    $cache->deleteMultiple($cids);

    $delete_query->condition($or);
    return $delete_query->execute();
  }

  /**
   * Triggers one or more errors.
   *
   * @param string|array $errors
   *   Errors to trigger.
   * @param mixed $flags
   *   If set to STREAM_URL_STAT_QUIET, no error or exception is triggered.
   *
   * @return bool
   *   Always returns FALSE.
   */
  protected function triggerError($errors, $flags = NULL) {
    if ($flags != STREAM_URL_STAT_QUIET) {
      trigger_error(implode("\n", (array) $errors), E_USER_ERROR);
    }
    $this->errorState = TRUE;
    return FALSE;
  }

}
