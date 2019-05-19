<?php

namespace Drupal\streamy\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Util;
use Twistor\StreamUtil;

/**
 * An adapter for Flysystem to StreamWrapperInterface.
 */
class DrupalFlySystemStreamWrapper implements StreamWrapperInterface {

  use StringTranslationTrait;

  const STREAM_URL_IGNORE_SIZE = 8;

  /**
   * Instance URI (stream).
   *
   * These streams will be references as 'session://example_target'
   *
   * @var String
   */
  protected $uri;

  /**
   * @var \Drupal\streamy\StreamWrapper\FlySystemHelper
   */
  protected $manager;

  /**
   * @var
   */
  protected $needsFlush;

  /**
   * @var
   */
  protected $bytesWritten;

  /**
   * @var
   */
  protected $handle;

  /**
   * @var
   */
  protected $isWriteOnly;

  /**
   * @var
   */
  protected $isReadOnly;

  /**
   * @var
   */
  protected $listing;

  /**
   * @var
   */
  protected $isAppendMode;

  /**
   * @var
   */
  protected $needsCowCheck;

  /**
   * @var
   */
  protected $streamWriteBuffer;

  /**
   * @var    \Drupal\Core\StreamWrapper\PublicStream
   */
  protected $streamWrapperPublic;

  /**
   * The default configuration.
   *
   * @var array
   */
  protected static $defaultConfiguration;

  /**
   * DrupalFlySystemStreamWrapper constructor.
   */
  public function __construct() {
    $this->streamWrapperPublic = \Drupal::service('stream_wrapper.public');
  }

  /**
   * @return \Drupal\streamy\StreamWrapper\FlySystemHelper
   */
  protected function getFilesystemManager() {
    $this->manager = \Drupal::service('streamy.factory')
                            ->getFilesystem($this->getProtocol());
    return $this->manager;
  }

  /**
   * @param array $fileSystems
   * @return mixed
   */
  protected function getFirstFileystem(array $fileSystems) {
    return array_shift(array_slice($fileSystems, 0, 1));
  }

  /**
   * @return int
   */
  public static function getType() {
    return StreamWrapperInterface::WRITE_VISIBLE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->getFilesystemManager()->getStreamPublicName();
    if (!$name) {
      $name = $this->t('Streamy (%protocol)', ['%protocol' => $this->getProtocol()]);
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = 'Streamy: ' . $this->getFilesystemManager()->getStreamPublicDescription();
    if (!$description) {
      $description = $this->t('Streamy: Stream Wrapper with Replica and CDN support (%protocol)', ['%protocol' => $this->getProtocol()]);
    }
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * @return string
   */
  protected function getStreamName() {
    return $this->getProtocol() . '://';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $filename = str_replace($this->getStreamName(), '', $this->uri);
    $streamFilename = trim($filename, '/');

    $path_parts = explode('/', $streamFilename);

    // If this is a /styles/ image request we need to handle it differently
    // in order to create the image if doesn't exist.
    // @see StreamyController:deliver
    if ($path_parts[0] == 'styles') {
      if (!file_exists($this->uri)) {
        list(, $imageStyle, $scheme) = array_splice($path_parts, 0, 3);

        return Url::fromRoute(
          'streamy.image_style',
          [
            'image_style' => $imageStyle,
            'file'        => implode('/', $path_parts),
            'scheme'      => $scheme,
          ]
        )->toString();
      }
    }

    $url = $this->getFilesystemManager()->getUrl($streamFilename, $this->uri);
    if ($url) {
      return $url;
    }

    return $this->getPublicURLFromDrupalDefaultPublicFolder();
  }

  /**
   * Last chance to get a public URL getting from the public folder
   * without taking care of its presence.
   *
   * @return string
   */
  protected function getPublicURLFromDrupalDefaultPublicFolder() {
    // Getting the base path for public://.
    $public_base_path = $this->streamWrapperPublic->basePath();
    $ds = DIRECTORY_SEPARATOR;
    return $GLOBALS['base_url'] . $ds . $public_base_path . $ds .
           UrlHelper::encodePath($this->getTarget());
  }

  /**
   * Returns the local writable target of the resource within the stream.
   *
   * @param string|null $uri The URI.
   *
   * @return string The path appropriate for use with Flysystem.
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $x = strpos($uri, '://');
    $target = substr($uri, $x + 3);

    return $target === FALSE ? '' : $target;
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);

    return $scheme . '://' . ltrim(Util::dirname($target), '\/');
  }

  /**
   * @return bool
   */
  public function stream_flush() {
    if (!$this->needsFlush) {
      return TRUE;
    }

    $this->needsFlush = FALSE;
    $this->bytesWritten = 0;

    // Calling putStream() will rewind our handle. flush() shouldn't change
    // the position of the file.
    $pos = ftell($this->handle);

    $success = $this->getFilesystemManager()
                    ->putStream($this->uri, $this->handle);

    fseek($this->handle, $pos);

    return $success;
  }

  /**
   * Retrieves the underlying resource.
   *
   * @param int $cast_as
   *
   * @return resource|bool The stream resource used by the wrapper, or false.
   */
  public function stream_cast($cast_as) {
    return $this->handle;
  }

  /**
   * Closes the resource.
   */
  public function stream_close() {
    // PHP 7 doesn't call flush automatically anymore for truncate() or when
    // writing an empty file. We need to ensure that the handle gets pushed
    // as needed in that case. This will be a no-op for php 5.
    $this->stream_flush();

    fclose($this->handle);
  }

  /**
   * Tests for end-of-file on a file pointer.
   *
   * @return bool
   *    True if the file is at the end, false if not.
   */
  public function stream_eof() {
    return feof($this->handle);
  }

  /**
   * Advisory file locking.
   *
   * @param int $operation
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_lock($operation) {
    // Normalize paths so that locks are consistent.
    $normalized = $this->getProtocol() . '://' .
                  Util::normalizePath($this->getTarget());

    // Relay the lock to a real filesystem lock.
    $lockfile = sys_get_temp_dir() . '/streamy-stream-wrapper-' .
                sha1($normalized) . '.lock';
    $handle = fopen($lockfile, 'w');
    $success = flock($handle, $operation);
    fclose($handle);

    return $success;
  }

  /**
   * Returns the protocol from the internal URI.
   *
   * @return string
   *   The protocol.
   */
  protected function getProtocol() {
    return substr($this->uri, 0, strpos($this->uri, '://'));
  }

  /**
   * Changes stream options.
   *
   * @param string $uri
   * @param int    $option
   * @param mixed  $value
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_metadata($uri, $option, $value) {
    $this->uri = $uri;

    switch ($option) {
      case STREAM_META_ACCESS:
        $permissions = octdec(substr(decoct($value), -4));
        $is_public = $permissions & $this->getConfiguration('public_mask');
        $visibility = $is_public ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;

        return $this->getFilesystemManager()->setVisibility($uri, $visibility);

      case STREAM_META_TOUCH:
        return $this->getFilesystemManager()->touch($uri);

      default:
        return FALSE;
    }
  }

  /**
   * Returns the configuration.
   *
   * @param string|null $key The optional configuration key.
   *
   * @return array The requested configuration.
   */
  protected function getConfiguration($key = NULL) {
    static::$defaultConfiguration = $this->getFilesystemManager()->getConfiguration();
    return $key ? self::$defaultConfiguration[$key] :
      static::$defaultConfiguration;
  }

  /**
   * Opens file or URL.
   *
   * @param string $uri
   * @param string $mode
   * @param int    $options
   * @param string &$opened_path
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->uri = $uri;
    $path = $this->getTarget();

    $this->isReadOnly = StreamUtil::modeIsReadOnly($mode);
    $this->isWriteOnly = StreamUtil::modeIsWriteOnly($mode);
    $this->isAppendMode = StreamUtil::modeIsAppendable($mode);

    $this->handle = $this->getStream($uri,
                                     $mode);

    if ($this->handle && $options & STREAM_USE_PATH) {
      $opened_path = $path;
    }

    return is_resource($this->handle);
  }

  /**
   * Reads from stream.
   *
   * @param int $count
   *
   * @return string The bytes read.
   */
  public function stream_read($count) {
    if ($this->isWriteOnly) {
      return '';
    }

    return fread($this->handle, $count);
  }

  /**
   * Seeks to specific location in a stream.
   *
   * @param int $offset
   * @param int $whence
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    return fseek($this->handle, $offset, $whence) === 0;
  }


  /**
   * Changes stream options.
   *
   * @param int $option
   * @param int $arg1
   * @param int $arg2
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    switch ($option) {
      case STREAM_OPTION_BLOCKING:
        // This works for the local adapter. It doesn't do anything for
        // memory streams.
        return stream_set_blocking($this->handle, $arg1);

      case STREAM_OPTION_READ_TIMEOUT:
        return stream_set_timeout($this->handle, $arg1, $arg2);

      case STREAM_OPTION_READ_BUFFER:
        if ($arg1 === STREAM_BUFFER_NONE) {
          return stream_set_read_buffer($this->handle, 0) === 0;
        }

        return stream_set_read_buffer($this->handle, $arg2) === 0;

      case STREAM_OPTION_WRITE_BUFFER:
        $this->streamWriteBuffer = $arg1 === STREAM_BUFFER_NONE ? 0 : $arg2;

        return TRUE;
    }

    return FALSE;
  }


  /**
   * Retrieves information about a file resource.
   *
   * @return array A similar array to fstat().
   *
   * @see fstat()
   */
  public function stream_stat() {
    // Get metadata from original file.
    $stat = $this->url_stat($this->uri,
                            static::STREAM_URL_IGNORE_SIZE |
                            STREAM_URL_STAT_QUIET) ?: [];

    // Newly created file.
    if (empty($stat['mode'])) {
      $stat['mode'] = 0100000 + $this->getConfiguration('permissions')['file']['public'];
      $stat[2] = $stat['mode'];
    }

    // Use the size of our handle, since it could have been written to or
    // truncated.
    $stat['size'] = $stat[7] = StreamUtil::getSize($this->handle);

    return $stat;
  }

  /**
   * Retrieves the current position of a stream.
   *
   * @return int The current position of the stream.
   */
  public function stream_tell() {
    if ($this->isAppendMode) {
      return 0;
    }
    return ftell($this->handle);
  }

  /**
   * Truncates the stream.
   *
   * @param int $new_size
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function stream_truncate($new_size) {
    if ($this->isReadOnly) {
      return FALSE;
    }
    $this->needsFlush = TRUE;
    $this->ensureWritableHandle();

    return ftruncate($this->handle, $new_size);
  }


  /**
   * Guarantees that the handle is writable.
   */
  protected function ensureWritableHandle() {
    if (!$this->needsCowCheck) {
      return;
    }

    $this->needsCowCheck = FALSE;

    if (StreamUtil::isWritable($this->handle)) {
      return;
    }

    $this->handle = StreamUtil::copy($this->handle);
  }


  /**
   * Writes to the stream.
   *
   * @param string $data
   *
   * @return int The number of bytes that were successfully stored.
   */
  public function stream_write($data) {
    if ($this->isReadOnly) {
      return 0;
    }
    $this->needsFlush = TRUE;
    $this->ensureWritableHandle();

    // Enforce append semantics.
    if ($this->isAppendMode) {
      StreamUtil::trySeek($this->handle, 0, SEEK_END);
    }

    $written = fwrite($this->handle, $data);
    $this->bytesWritten += $written;

    if (isset($this->streamWriteBuffer) &&
        $this->bytesWritten >= $this->streamWriteBuffer
    ) {
      $this->stream_flush();
    }

    return $written;
  }

  /**
   * Deletes a file.
   *
   * @param string $uri
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function unlink($uri) {
    $this->uri = $uri;

    return $this->getFilesystemManager()->deleteFile($uri);
  }

  /**
   * Retrieves information about a file.
   *
   * @param string $uri
   * @param int    $flags
   *
   * @return bool|array Output similar to stat().
   *
   * @see stat()
   */
  public function url_stat($uri, $flags) {
    if (!$this->uri) {
      $this->uri = $uri;
    }
    $meta = $this->getFilesystemManager()->stat($uri, $flags);
    if (!count($meta) || !is_array($meta)) {
      return FALSE;
    }

    return $meta;
  }

  /**
   * Returns a stream for a given path and mode.
   *
   * @param string $path The path to open.
   * @param string $mode The mode to open the stream in.
   *
   * @return resource|bool The file handle, or false.
   */
  protected function getStream($path, $mode) {
    switch ($mode[0]) {
      case 'r':
        $this->needsCowCheck = TRUE;
        return $this->getFilesystemManager()->readStream($path);

      case 'w':
        $this->needsFlush = TRUE;
        return fopen('php://temp', 'w+b');

      case 'a':
        return $this->getAppendStream($path);

      case 'x':
        return $this->getXStream($path);

      case 'c':
        return $this->getWritableStream($path);
    }

    return FALSE;
  }

  /**
   * Returns an appendable stream for a given path and mode.
   *
   * @param string $path The path to open.
   *
   * @return resource|bool The file handle, or false.
   */
  protected function getAppendStream($path) {
    if ($handle = $this->getWritableStream($path)) {
      StreamUtil::trySeek($handle, 0, SEEK_END);
    }

    return $handle;
  }

  /**
   * Returns a writable stream for a given path and mode.
   *
   * @param string $path The path to open.
   *
   * @return resource|bool The file handle, or false.
   */
  protected function getWritableStream($path) {
    try {
      $handle = $this->getFilesystemManager()->readStream($path);
      $this->needsCowCheck = TRUE;

    } catch (FileNotFoundException $e) {
      $handle = fopen('php://temp', 'w+b');
      $this->needsFlush = TRUE;
    }

    return $handle;
  }

  /**
   * Returns a writable stream for a given path and mode.
   *
   * Triggers a warning if the file exists.
   *
   * @param string $path The path to open.
   *
   * @return resource|bool The file handle, or false.
   */
  protected function getXStream($path) {
    if ($this->getFilesystemManager()->has($path)) {
      trigger_error('fopen(): failed to open stream: File exists',
                    E_USER_WARNING);

      return FALSE;
    }

    $this->needsFlush = TRUE;

    return fopen('php://temp', 'w+b');
  }

  /**
   * @return bool
   */
  public function dir_closedir() {
    unset($this->listing);

    return TRUE;
  }

  /**
   * Opens a directory handle.
   *
   * @param string $uri     The URL that was passed to opendir().
   * @param int    $options Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *    True on success, false on failure.
   */
  public function dir_opendir($uri, $options) {
    $this->uri = $uri;

    $path = Util::normalizePath($this->getTarget());

    $this->listing = $this->getFilesystemManager()->listContents($path);

    if ($this->listing === FALSE) {
      return FALSE;
    }

    if (!$dirlen = strlen($path)) {
      return TRUE;
    }

    // Remove the separator /.
    $dirlen++;

    // Remove directory prefix.
    foreach ($this->listing as $delta => $item) {
      $this->listing[$delta]['path'] = substr($item['path'], $dirlen);
    }

    reset($this->listing);

    return TRUE;
  }

  /**
   * @return string
   */
  public function dir_readdir() {
    $current = current($this->listing);
    next($this->listing);

    return $current ? $current['path'] : FALSE;
  }

  /**
   * @return bool
   */
  public function dir_rewinddir() {
    reset($this->listing);

    return TRUE;
  }

  /**
   * @return bool
   */
  public function mkdir($uri, $mode, $options) {
    $this->uri = $uri;

    return $this->getFilesystemManager()->createDir($uri, $mode, $options);
  }

  /**
   * @return bool
   */
  public function rename($uri_from, $uri_to) {
    $this->uri = $uri_from;

    return $this->getFilesystemManager()->forcedRename($uri_from, $uri_to);
  }

  /**
   * @return bool
   */
  public function rmdir($uri, $options) {
    $this->uri = $uri;

    return $this->getFilesystemManager()->rmdir($uri, $options);
  }
}
