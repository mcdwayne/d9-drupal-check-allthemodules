<?php

namespace Drupal\ossfs\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use GuzzleHttp\Psr7\Stream;
use OSS\Core\MimeTypes;
use OSS\Core\OssException;
use OSS\OssClient;
use Psr\Http\Message\StreamInterface;

/**
 * Aliyun OSS stream wrapper to use "oss://key" files with PHP
 * streams, supporting "r", "w", "a", "x".
 *
 * # Opening "r" (read only) streams:
 *
 * Read only streams are truly streaming by default and will not allow you to
 * seek. This is because data read from the stream is not kept in memory or on
 * the local filesystem. You can force a "r" stream to be seekable by setting
 * the "seekable" stream context option true. This will allow true streaming of
 * data from Aliyun OSS, but will maintain a buffer of previously read bytes in
 * a 'php://temp' stream to allow seeking to previously read bytes from the
 * stream.
 * @code
 *    $default = stream_context_get_options(stream_context_get_default());
 *    $default[static::PROTOCOL]['seekable'] = TRUE;
 *    stream_context_set_default($default);
 * @endcode
 *
 * You may pass any GetObject parameters as 'oss' stream context options. These
 * options will affect how the data is downloaded from Aliyun OSS.
 *
 * # Opening "w" and "x" (write only) streams:
 *
 * Because Aliyun OSS requires a Content-Length header, write only streams will
 * maintain a 'php://temp' stream to buffer data written to the stream until
 * the stream is flushed (usually by closing the stream with fclose).
 *
 * You may pass any PutObject parameters as 'oss' stream context options. These
 * options will affect how the data is uploaded to Aliyun OSS.
 *
 * When opening an "x" stream, the file must exist on Aliyun OSS for the stream
 * to open successfully.
 *
 * # Opening "a" (write only append) streams:
 *
 * Similar to "w" streams, opening append streams requires that the data be
 * buffered in a "php://temp" stream. Append streams will attempt to download
 * the contents of an object in Aliyun OSS, seek to the end of the object, then
 * allow you to append to the contents of the object. The data will then be
 * uploaded using a PutObject operation when the stream is flushed (usually
 * with fclose).
 *
 * You may pass any GetObject and/or PutObject parameters as 'oss' stream
 * context options. These options will affect how the data is downloaded and
 * uploaded from Aliyun OSS.
 *
 * Stream context options:
 *
 * - "seekable": Set to true to create a seekable "r" (read only) stream by
 *   using a php://temp stream buffer
 *
 * - For "unlink" only: Any option that can be passed to the DeleteObject
 *   operation
 *
 * @todo: make sure $this->context is set by php and refactor getOptions()
 */
class OssfsStream implements StreamWrapperInterface {

  /**
   * Stream context (this is set by PHP).
   *
   * Note: This property must be public so PHP can populate it with the actual
   * context resource.
   *
   * @var resource|null
   */
  public $context;

  /**
   * The ossfs storage.
   *
   * @var \Drupal\ossfs\OssfsStorageInterface
   */
  protected $storage;

  /**
   * The configuration for oss.
   *
   * @var array
   */
  protected $config;

  /**
   * The oss client.
   *
   * @var \OSS\OssClient
   */
  protected $client;

  /**
   * Instance uri referenced as "oss://key".
   *
   * @var string
   */
  protected $uri;

  /**
   * Underlying php stream resource.
   *
   * @var resource
   */
  protected $stream;

  /**
   * Stream resource.
   *
   * @var \Psr\Http\Message\StreamInterface
   */
  protected $body;

  /**
   * Size of the body that is opened.
   *
   * @var int
   */
  protected $size;

  /**
   * Hash of opened stream parameters.
   *
   * @var array
   */
  protected $params = [];

  /**
   * Mode in which the stream was opened.
   *
   * @var string
   */
  protected $mode;

  /**
   * Objects used with opendir() related calls.
   *
   * @var array
   */
  protected $objects;

  /**
   * The protocol.
   *
   * @var string
   */
  const PROTOCOL = 'oss';

  /**
   * Returns the storage service.
   *
   * @return \Drupal\ossfs\OssfsStorageInterface
   */
  protected function getStorage() {
    if (!$this->storage) {
      $this->storage = \Drupal::service('ossfs.storage');
    }
    return $this->storage;
  }

  /**
   * Returns the ossfs configuration.
   */
  protected function getConfig() {
    if (!$this->config) {
      $this->config = \Drupal::config('ossfs.settings')->get();
      unset($this->config['_core']);
    }
    return $this->config;
  }

  /**
   * Returns the OSS client.
   *
   * @return \OSS\OssClient
   */
  protected function getClient() {
    if (!$this->client) {
      $config = $this->getConfig();
      // It's safe to construct the endpoint manually (prefer internal)
      // regardless of the 'cname' setting for OssClient API calls.
      // To enable SSL: $client->setUseSSL(TRUE).
      $endpoint = $config['region'] . ($config['internal'] ? '-internal' : '') . '.aliyuncs.com';
      $this->client = new OssClient($config['access_key'], $config['secret_key'], $endpoint, FALSE);
    }
    return $this->client;
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
    return t('OSS File System');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Aliyun Object Storage Service.');
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
   * Do not return a local file for realpath, always returns FALSE.
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $config = $this->getConfig();
    $prefix = (string) $config['prefix'];
    $prefix = $prefix === '' ? '' : '/' . UrlHelper::encodePath($prefix);
    // Defaults to an external endpoint.
    $cname = $config['cname'] ?: $config['bucket'] . '.' . $config['region'] . '.aliyuncs.com';
    // Always use HTTPS when the page is being served via HTTPS, to avoid
    // complaints from the browser about insecure content.
    $url_prefix = \Drupal::request()->getScheme() . '://' . $cname . $prefix;

    list(, $target) = explode('://', $this->uri, 2);

    if (strpos($target, 'styles/') === 0 && substr_count($target, '/') >= 3) {
      list(, $style, $scheme, $file) = explode('/', $target, 4);
      // Handle image style preview at "/admin/config/media/image-styles/manage/large"
      // for core image module, see template_preprocess_image_style_preview().
      if ($file === 'core/modules/image/sample.png') {
        return $url_prefix . '/' . $target;
      }

      $oss_style = $config['styles'][$style];
      return $url_prefix . '/' . UrlHelper::encodePath($file) . '?x-oss-process=style/' . $oss_style;
    }

    return $url_prefix . '/' . UrlHelper::encodePath($target);
  }

  /**
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
   * @link http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->uri = $uri;
    $this->params = $this->getBucketKey($uri);
    $this->mode = rtrim($mode, 'bt');

    $raise_error = ($options & STREAM_REPORT_ERRORS) === STREAM_REPORT_ERRORS;

    if ($error = $this->validateOpen($uri, $this->mode)) {
      if ($raise_error) {
        trigger_error($error, E_USER_WARNING);
      }
      return FALSE;
    }

    if ($this->mode === 'r' && ($error = $this->ensureFileExists($uri))) {
      if ($raise_error) {
        trigger_error($error, E_USER_WARNING);
      }
      return FALSE;
    }

    try {
      switch ($this->mode) {
        case 'r':
          return $this->openReadStream();
        case 'a':
          return $this->openAppendStream();
        default:
          return $this->openWriteStream();
      }
    }
    catch (\Exception $e) {
      if ($raise_error) {
        trigger_error($e->getMessage(), E_USER_WARNING);
      }
      return FALSE;
    }
  }

  /**
   * Validates the provided stream arguments for fopen and returns errors.
   *
   * @return string
   *   an error if found, empty string otherwise.
   */
  protected function validateOpen($uri, $mode) {
    if (!$this->getOption('key')) {
      return 'Cannot open a bucket. You must specify a path in the form of oss://key';
    }

    if (!in_array($mode, ['r', 'w', 'a', 'x'])) {
      return "Mode not supported: {$mode}. Use one of 'r', 'w', 'a', or 'x', flavoured with t, b and/or +.";
    }

    // When using mode "x" check if the file exists before attempting to read.
    if ($mode == 'x' && $this->getStorage()->exists($uri)) {
      return "{$uri} already exists on OSS";
    }

    return '';
  }

  /**
   * Ensures the file exists.
   *
   * @param string $uri
   *   The file uri.
   *
   * @return string
   *   The error message if file not found, empty string otherwise.
   */
  protected function ensureFileExists($uri) {
    $metadata = $this->getStorage()->read($uri);
    if (!$metadata) {
      return 'No such file or directory';
    }
    if ($metadata['type'] !== 'file') {
      return 'Not a regular file';
    }
    return '';
  }

  protected function openReadStream() {
    $params = $this->getOptions(TRUE);
    $stream = fopen('php://temp', 'r+b');
    // The underlying methods throw an exception if the object is not found.
    $this->getClient()->getObject($params['bucket'], $params['key'], [
      OssClient::OSS_FILE_DOWNLOAD => $stream,
    ]);

    // @todo: Update or replace BodyResult to response to content-length.
    // Get the body of the object and seek to the begin of the stream.
    // $this->size = $stream->tell(); // size will be stated, see stream_stat()
    $this->stream = $stream;
    $this->body = new Stream($stream);
    // 'php://temp' is always seekable
    $this->body->seek(0);

    // Wrap the body in a caching entity body if seeking is allowed, but
    // the 'php://temp' is always seekable.
    // if ($this->getOption('seekable') && !$this->body->isSeekable()) {
    //   $this->body = new CachingStream($this->body);
    // }

    return TRUE;
  }

  protected function openWriteStream() {
    // The stream will be read in flush().
    $stream = fopen('php://temp', 'r+b');
    $this->stream = $stream;
    $this->body = new Stream($stream);
    return TRUE;
  }

  protected function openAppendStream() {
    $params = $this->getOptions(TRUE);
    $stream = fopen('php://temp', 'r+b');
    try {
      // Assume the file exists in OSS.
      $this->getClient()->getObject($params['bucket'], $params['key'], [
        OssClient::OSS_FILE_DOWNLOAD => $stream,
      ]);
    }
    catch (OssException $e) {
      // The object does not exist, so use a simple write stream.
      return $this->openWriteStream();
    }

    // Get the body of the object and seek to the end of the stream.
    $this->stream = $stream;
    $this->body = new Stream($stream);
    $this->body->seek(0, SEEK_END);
    return TRUE;
  }

  /**
   * Support for fflush().
   *
   * Flush current cached stream data to a file in OSS.
   *
   * @return bool
   *   TRUE if data was successfully stored in OSS.
   *
   * @link http://php.net/manual/en/streamwrapper.stream-flush.php
   */
  public function stream_flush() {
    if ($this->mode === 'r') {
      return FALSE;
    }

    clearstatcache(TRUE, $this->uri);

    if ($this->body->isSeekable()) {
      $this->body->seek(0);
    }

    $extension = strtolower(pathinfo($this->uri, PATHINFO_EXTENSION));
    $imagesize = static::getImagesize($this->body, $extension);
    if ($this->body->isSeekable()) {
      $this->body->seek(0);
    }

    // Save width, height and image type.
    $imagesize_string = $imagesize ? implode(',', array_slice($imagesize, 0, 3)) : '';
    $content_type = $imagesize ? $imagesize['mime'] : (MimeTypes::getMimetype($this->uri) ?: OssClient::DEFAULT_CONTENT_TYPE);
    $content_length = $this->getSize();

    try {
      $params = $this->getOptions(TRUE);
      $response = $this->getClient()->uploadStream($params['bucket'], $params['key'], $this->stream, [
        OssClient::OSS_CONTENT_TYPE => $content_type,
        OssClient::OSS_CONTENT_LENGTH => $content_length,
      ]);
      // Construct the data manually instead of requesting metadata from OSS.
      $data = [
        'uri' => $this->uri,
        'type' => 'file',
        'filemime' => $content_type,
        'filesize' => $content_length,
        'imagesize' => $imagesize_string,
        'changed' => time(), // time() is better than REQUEST_TIME in this case.
      ];
      $this->getStorage()->write($this->uri, $data);
      return TRUE;
    }
    catch (\Exception $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return FALSE;
    }
  }

  /**
   * Gets image size from the given stream.
   *
   * Similar function to getimagesize(), but instead read bytes from a stream.
   *
   *            Bytes to read (as per php/php-src)
   * -------------------------------------------------------
   *       | php_getimagetype | php_handle_*        | total
   * -------------------------------------------------------
   *  gif  | 3                | +3 (seek) +5 (read) | 11
   *  png  | 8                | +8 (seek) +9 (read) | 25
   *  jpeg | 3                | ...                 | ...
   * -------------------------------------------------------
   *
   * @link https://github.com/php/php-src/blob/master/ext/standard/image.c#L1370
   *
   * @param \Psr\Http\Message\StreamInterface $stream
   *   The stream to read from.
   * @param string $extension
   *   The extension.
   *
   * @return array|bool
   *   An array of image size info on success, FALSE otherwise.
   *
   * @see getimagesizefromstring()
   *
   * @link https://stackoverflow.com/a/29401572/3414249
   */
  public static function getImagesize(StreamInterface $stream, $extension) {
    switch ($extension) {
      case 'gif':
        $length = 11;
        break;
      case 'png':
        $length = 25;
        break;
      default:
        $length = 10240;
        break;
    }
    $data = $stream->read($length);
    // This function does not require the GD image library.
    $result = @getimagesizefromstring($data);
    if (!$result && $extension === 'jpg') {
      // Try again with double size
      $data .= $stream->read($length);
      $result = @getimagesizefromstring($data);
    }

    return $result;
  }

  /**
   * This wrapper does not support flock().
   *
   * @return bool
   *   Always returns FALSE.
   *
   * @link http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    return FALSE;
  }

  /**
   * Support for fread() and fgets().
   *
   * @link http://php.net/manual/en/streamwrapper.stream-read.php
   */
  public function stream_read($count) {
    return $this->body->read($count);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    if (!$this->body->isSeekable()) {
      return FALSE;
    }
    $this->body->seek($offset, $whence);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_tell() {
    return $this->body->tell();
  }

  /**
   * {@inheritdoc}
   */
  public function stream_write($data) {
    return $this->body->write($data);
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support touch(), chmod(), chown(), or chgrp().
   *
   * Manual recommends return FALSE for not implemented options, but Drupal
   * require TRUE in some cases like chmod to avoid watchdog errors.
   *
   * Returns FALSE if the option is not included in bypassed_options array
   * otherwise, TRUE.
   *
   * @see \Drupal\Core\File\FileSystem::chmod()
   * @link http://php.net/manual/en/streamwrapper.stream-metadata.php
   */
  public function stream_metadata($uri, $option, $value) {
    $bypassed_options = [STREAM_META_ACCESS];
    return in_array($option, $bypassed_options);
  }

  /**
   * {@inheritdoc}
   *
   * @link http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  public function stream_stat() {
    $stat = $this->getStatTemplate();
    $stat[7] = $stat['size'] = $this->getSize();
    $stat[2] = $stat['mode'] = 0100777;

    return $stat;
  }

  /**
   * {@inheritdoc}
   *
   * Since Windows systems do not allow it and it is not needed for most use
   * cases anyway, this method is not supported on OSS and will trigger an error
   * and return FALSE.
   *
   * @link http://php.net/manual/en/streamwrapper.stream-set-option.php
   */
  public function stream_set_option($option, $arg1, $arg2) {
    trigger_error('stream_set_option() not supported', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support stream_truncate().
   *
   * Always returns FALSE.
   *
   * @link http://php.net/manual/en/streamwrapper.stream-truncate.php
   */
  public function stream_truncate($new_size) {
    return FALSE;
  }

  /**
   * Support for feof().
   *
   * @return bool
   *  TRUE if the read/write position is at the end of the stream and if no more
   *  data is available to be read, or FALSE otherwise.
   *
   * @link http://php.net/manual/en/streamwrapper.stream-eof.php
   */
  public function stream_eof() {
    return $this->body->eof();
  }

  /**
   * Support for fclose().
   *
   * @link http://php.net/manual/en/streamwrapper.stream-close.php
   */
  public function stream_close() {
    $this->body->close();
    $this->body = $this->stream = NULL;
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   The uri of the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted, regardless of whether or not
   *   the file actually existed.
   *   FALSE if the call to OSS failed, in which case the file will not be
   *   removed from the cache.
   *
   * @link http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $this->uri = $uri;
    clearstatcache(TRUE, $uri);

    try {
      $params = $this->withUri($uri);
      $this->getClient()->deleteObject($params['bucket'], $params['key']);
      $this->getStorage()->delete($uri);
      return TRUE;
    }
    catch (\Exception $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return FALSE;
    }
  }

  /**
   * Retrieves information about a file.
   *
   * This method is called in response to all stat() related functions:
   * filesize, is_writable, is_dir, is_file, stat, etc. Works on
   * buckets, keys, and prefixes.
   *
   * @param string $uri
   *   The URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *   This wrapper ignores this value.
   *
   * @return array|bool
   *   An array with file status, or FALSE in case of an error.
   *
   * @link http://www.php.net/manual/en/streamwrapper.url-stat.php
   */
  public function url_stat($uri, $flags) {
    $this->uri = $uri;

    list(, $target) = explode('://', $uri, 2);
    if ($target === '' || $target === '/') {
      // For the root "oss://", return a generic directory stat.
      return $this->formatUrlStat(['type' => 'dir']);
    }

    $clean_uri = rtrim($uri, '/');
    if ($metadata = $this->getStorage()->read($clean_uri)) {
      return $this->formatUrlStat($metadata);
    }

    if (($flags & STREAM_URL_STAT_QUIET) != STREAM_URL_STAT_QUIET) {
      trigger_error("File or directory not found: $uri", E_USER_WARNING);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    list($scheme, $target) = explode('://', $uri, 2);
    // dirname() will strip trailing slashes, and then if there are no slashes
    // in path, a dot ('.') is returned, indicating the current directory.
    $dirname = dirname($target);
    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }

  /**
   * {@inheritdoc}
   *
   * @link http://www.php.net/manual/en/streamwrapper.mkdir.php
   *
   * @see \Drupal\Core\StreamWrapper\LocalStream::mkdir()
   */
  public function mkdir($uri, $mode, $options) {
    $this->uri = $uri;
    clearstatcache(TRUE, $uri);

    list(, $target) = explode('://', $uri, 2);
    if ($target === '' || $target === '/') {
      // For the root "oss://", return TRUE.
      return TRUE;
    }

    $clean_uri = rtrim($uri, '/');
    if ($this->getStorage()->exists($clean_uri)) {
      trigger_error('File exists: ' . $uri, E_USER_WARNING);
      return FALSE;
    }

    // Ensure any trailing slashes are trimmed out because they can throw off
    // the loop when creating the parent directories, and we don't store the
    // trailing '/' for a directory in local storage.
    list($scheme, $target) = explode('://', $clean_uri, 2);
    // If recursive, create each missing component of the parent directory
    // individually.
    if ($options & STREAM_MKDIR_RECURSIVE) {
      // Determine the components of the path.
      $components = explode('/', $target);
      $recursive_uri = $scheme . '://';
      // Don't handle the top-level directory in this loop.
      array_pop($components);
      // Create each component if necessary.
      foreach ($components as $component) {
        $recursive_uri .= $component;
        if (!file_exists($recursive_uri)) {
          if (!$this->mkdirCall($recursive_uri, $mode)) {
            return FALSE;
          }
        }
        $recursive_uri .= '/';
      }
    }

    // Do not check if the top-level directory already exists.
    return $this->mkdirCall($clean_uri, $mode);
  }

  /**
   * Only create the directory on the local storage.
   */
  protected function mkdirCall($uri, $mode) {
    $data = [
      'uri' => $uri,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      'changed' => REQUEST_TIME,
    ];
    return $this->getStorage()->write($uri, $data);
  }

  /**
   * {@inheritdoc}
   *
   * Support for opendir().
   *
   * @link http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options) {
    $this->uri = $uri;

    list($scheme, $target) = explode('://', $uri, 2);
    $root = ($target === '' || $target === '/') ? $scheme . '://' : FALSE;

    $clean_uri = rtrim($uri, '/');
    if (!$root && ($error = $this->ensureDirExists($clean_uri))) {
      trigger_error($error, E_USER_WARNING);
      return FALSE;
    }

    $this->objects = array_map(function ($child_uri) {
      return basename($child_uri);
    }, $this->getStorage()->listAll($root ?: ($clean_uri . '/')));
    return TRUE;
  }

  /**
   * Ensures the directory exists.
   *
   * @param string $uri
   *   The directory uri.
   *
   * @return string
   *   An error message if directory not found, empty string otherwise.
   */
  protected function ensureDirExists($uri) {
    $metadata = $this->getStorage()->read($uri);
    if (!$metadata) {
      return 'No such file or directory';
    }
    if ($metadata['type'] !== 'dir') {
      return 'Not a directory';
    }
    return '';
  }

  /**
   * Support for readdir().
   *
   * @return string|bool
   *   Returns a string representing the next filename, or FALSE if there is
   *   no next file.
   *
   * @link http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
  public function dir_readdir() {
    // current() returns FALSE when beyond the end.
    $name = current($this->objects);
    next($this->objects);
    return $name;
  }

  /**
   * Support for rewinddir().
   *
   * @return boolean
   *   TRUE on success.
   */
  public function dir_rewinddir() {
    reset($this->objects);
    return TRUE;
  }

  /**
   * Close the directory listing handles
   *
   * @return bool
   *   TRUE on success.
   */
  public function dir_closedir() {
    $this->objects = NULL;
    gc_collect_cycles();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @link http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    $this->uri = $uri;
    clearstatcache(TRUE, $uri);

    list(, $target) = explode('://', $uri, 2);
    if ($target === '' || $target === '/') {
      // For the root "oss://", return FALSE.
      return FALSE;
    }

    $clean_uri = rtrim($uri, '/');
    if ($error = $this->ensureDirExists($clean_uri)) {
      trigger_error($error, E_USER_WARNING);
      return FALSE;
    }
    // Can only remove empty directories.
    // listAll() is not efficient in the case of checking children's existence,
    // but rmdir() is rarely happened in Drupal.
    if ($this->getStorage()->listAll($clean_uri . '/')) {
      trigger_error('Directory is not empty', E_USER_WARNING);
      return FALSE;
    }

    return $this->getStorage()->delete($clean_uri);
  }

  /**
   * Support for rename().
   *
   * Called in response to rename() to rename a file or directory. Currently
   * only supports renaming objects.
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
   *   TRUE if file was successfully renamed. FALSE otherwise.
   *
   * @link http://www.php.net/manual/en/streamwrapper.rename.php
   *
   * @todo: Currently only file is assumed, implement rename directory.
   */
  public function rename($from_uri, $to_uri) {
    clearstatcache(TRUE, $from_uri);
    clearstatcache(TRUE, $to_uri);
    // PHP will not allow rename across wrapper types, so we can safely
    // assume $path_from and $path_to have the same protocol.
    $parts_from = $this->withUri($from_uri);
    $parts_to = $this->withUri($to_uri);

    if (!$parts_from['key'] || !$parts_to['key']) {
      trigger_error('OSS only supports copying objects', E_USER_WARNING);
      return FALSE;
    }

    if ($error = $this->ensureFileExists($from_uri)) {
      trigger_error($error, E_USER_WARNING);
      return FALSE;
    }

    try {
      // Copy the object and allow overriding default parameters if desired,
      // but by default copy metadata.
      $this->getClient()->copyObject($parts_from['bucket'], $parts_from['key'], $parts_to['bucket'], $parts_to['key']);
      // Delete the original object.
      $this->getClient()->deleteObject($parts_from['bucket'], $parts_from['key']);
      return $this->getStorage()->rename($from_uri, $to_uri);
    }
    catch (\Exception $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * Gets the stream context options available to the current stream.
   *
   * @param bool $removeContextData
   *   Set to true to remove contextual kvp's like 'seekable' from the result.
   *
   * @return array
   */
  protected function getOptions($removeContextData = FALSE) {
    // Context is not set when doing things like stat
    if ($this->context === NULL) {
      $options = [];
    }
    else {
      $options = stream_context_get_options($this->context);
      $options = isset($options[static::PROTOCOL]) ? $options[static::PROTOCOL] : [];
    }

    $default = stream_context_get_options(stream_context_get_default());
    $default = isset($default[static::PROTOCOL]) ? $default[static::PROTOCOL] : [];
    $result = $this->params + $options + $default;

    if ($removeContextData) {
      unset($result['seekable']);
    }

    return $result;
  }

  /**
   * Get a specific stream context option
   *
   * @param string $name Name of the option to retrieve
   *
   * @return mixed|null
   */
  protected function getOption($name) {
    $options = $this->getOptions();
    return isset($options[$name]) ? $options[$name] : NULL;
  }

  protected function getBucketKey($uri) {
    $config = $this->getConfig();
    $prefix = (string) $config['prefix'];
    $prefix = $prefix === '' ? '' : UrlHelper::encodePath($prefix) . '/';
    list(, $target) = explode('://', $uri, 2);

    return [
      'bucket' => $config['bucket'],
      'key' => $prefix . UrlHelper::encodePath($target),
    ];
  }

  /**
   * Gets the bucket and key from the passed uri (e.g. oss://key).
   *
   * @param string $uri
   *   Uri passed to the stream wrapper.
   *
   * @return array
   *   Hash of 'bucket', 'key', and custom params from the context.
   */
  protected function withUri($uri) {
    $params = $this->getOptions(TRUE);
    return $this->getBucketKey($uri) + $params;
  }

  /**
   * Prepares a *_stat result array.
   *
   * @param array $metadata
   *   The metadata.
   *
   * @return array
   *   Returns the modified *_stat result.
   */
  protected function formatUrlStat(array $metadata) {
    $stat = $this->getStatTemplate();
    $metadata += [
      'type' => 'unknown',
    ];
    if ($metadata['type'] === 'dir') {
      // Directory with 0777 access - see "man 2 stat".
      $stat['mode'] = $stat[2] = 0040777;
    }
    elseif ($metadata['type'] === 'file') {
      // Regular file with 0777 access - see "man 2 stat".
      $stat['mode'] = $stat[2] = 0100777;
      $stat['size'] = $stat[7] = (int) $metadata['filesize'];
      $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10] = (int) $metadata['changed'];
    }
    return $stat;
  }

  /**
   * Gets a URL stat template with default values
   *
   * @return array
   */
  protected function getStatTemplate() {
    return [
      0  => 0,  'dev'     => 0,
      1  => 0,  'ino'     => 0,
      2  => 0,  'mode'    => 0,
      3  => 0,  'nlink'   => 0,
      4  => 0,  'uid'     => 0,
      5  => 0,  'gid'     => 0,
      6  => -1, 'rdev'    => -1,
      7  => 0,  'size'    => 0,
      8  => 0,  'atime'   => 0,
      9  => 0,  'mtime'   => 0,
      10 => 0,  'ctime'   => 0,
      11 => -1, 'blksize' => -1,
      12 => -1, 'blocks'  => -1,
    ];
  }

  /**
   * Returns the size of the opened object body.
   *
   * @return int|null
   */
  protected function getSize() {
    $size = $this->body->getSize();
    return $size !== NULL ? $size : $this->size;
  }

}
