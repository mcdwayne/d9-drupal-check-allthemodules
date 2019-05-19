<?php

namespace Drupal\yandexdisk\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\yandexdisk\YandexDiskException;

/**
 * Defines a Yandex.Disk (yandexdisk://) stream wrapper class.
 *
 * Provides support to work with users Disks via filesystem functions.
 */
class YandexDiskStreamWrapper implements StreamWrapperInterface {
  use StringTranslationTrait;

  /**
   * Indicates that stream was open for reading.
   */
  const OPEN_FOR_READ = 1;

  /**
   * Indicates that stream was open for writing.
   */
  const OPEN_FOR_WRITE = 2;

  /**
   * Stream context resource.
   *
   * @var resource
   */
  public $context;

  /**
   * A generic resource handle used for writing temporary files.
   *
   * @var resource
   */
  public $handle;

  /**
   * Instance URI.
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * Yandex.Disk account instance.
   *
   * @var \Drupal\yandexdisk\YandexDiskApiWebdavHelper
   */
  protected $disk;

  /**
   * Username of Yandex.Disk account.
   *
   * @var string
   */
  public $user;

  /**
   * File path of the stream beginning with a slash.
   *
   * @var string
   */
  public $path;

  /**
   * Stream open mode.
   *
   * @var int
   */
  protected $openMode;

  /**
   * Pointer position in stream.
   *
   * @var int
   */
  protected $position;

  /**
   * Internal read buffer which prevents too many requests to Disk.
   *
   * @var string[]
   */
  protected $buffer;

  /**
   * List of directory contents for use with readdir().
   *
   * @var string[]
   */
  protected $directoryContents = [];

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Yandex.Disk');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Files served by Yandex.Disk cloud service.');
  }

  /**
   * Returns Disk class instance for current Disk account.
   *
   * @return \Drupal\yandexdisk\YandexDiskApiWebdavHelper|null
   *   Disk class instance.
   */
  public function disk() {
    if (!isset($this->disk) && isset($this->user)) {
      try {
        $this->disk = \Drupal::service('yandexdisk_manager')->getDisk($this->user);
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e, 'Request failed for @stream: @message', ['@stream' => $this->uri]);
      }
    }

    return $this->disk;
  }

  /**
   * {@inheritdoc}
   *
   * @return bool
   *   TRUE in case $uri is valid, FALSE otherwise.
   */
  public function setUri($uri) {
    // Characters "#", "?" and "&" are valid for YandexDisk paths. So
    // parse_url() is redundant here.
    $target = file_uri_target($uri);

    if ($target !== FALSE && $target !== '') {
      $this->uri = $uri;

      @list($this->user, $path) = explode('/', $target, 2);
      $this->path = '/' . $path;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Opens file resource.
   *
   * @param string $uri
   *   The URL that was passed to the original function.
   * @param string $mode
   *   The mode used to open the file. Only the following modes supported:
   *   - 'r': Read-only. The method will fail if file does not exist.
   *   - 'w': Write-only.
   *   - 'c': Same as 'w' in this implementation.
   *   - 'x': Write-only. But the method will fail if file exists already.
   *   A '+' sign is not supported in mode.
   * @param int $options
   *   Additional flags set by the streams API. It can hold one or more of the
   *   following values OR'd together: STREAM_USE_PATH, STREAM_REPORT_ERRORS.
   * @param string $opened_url
   *   If the path is opened successfully, and STREAM_USE_PATH is set in
   *   options, opened_url should be set to the full path of the file/resource
   *   that was actually opened.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function stream_open($uri, $mode, $options, &$opened_url) {
    $success = FALSE;
    $error_reported = FALSE;

    // We don't support mutual read-n-write mode.
    if (@$mode[1] != '+' && $this->setUri($uri) && ($disk = $this->disk())) {
      $path = $this->path;

      try {
        // Check if the $mode is valid for the requested $uri.
        switch ($mode[0]) {
          case 'r':
            $success = $disk->isFile($path);
            if (!$success && $options & STREAM_REPORT_ERRORS) {
              trigger_error('No such file', E_USER_WARNING);
              $error_reported = TRUE;
            }
            break;

          case 'w':
          case 'c':
            try {
              $success = $disk->write($path, '');
            }
            catch (YandexDiskException $e) {
              watchdog_exception('yandexdisk', $e, 'Cannot create file @stream: @message', ['@stream' => $this->uri]);
              if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Cannot create file', E_USER_WARNING);
                $error_reported = TRUE;
              }
            }
            break;

          case 'x':
            $success = !$disk->isFile($path);
            if (!$success && $options & STREAM_REPORT_ERRORS) {
              trigger_error('File exists', E_USER_WARNING);
              $error_reported = TRUE;
            }
            if ($success) {
              try {
                $disk->write($path, '');
              }
              catch (YandexDiskException $e) {
                $success = FALSE;
                watchdog_exception('yandexdisk', $e, 'Cannot create file @stream: @message', ['@stream' => $this->uri]);
                if ($options & STREAM_REPORT_ERRORS) {
                  trigger_error('Cannot create file', E_USER_WARNING);
                  $error_reported = TRUE;
                }
              }
            }
            break;
        }
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e);
      }

      if ($success) {
        // Set pointer position and clear the buffer.
        $this->position = 0;
        $this->buffer = [];

        // Remember open mode.
        if ($mode[0] == 'r') {
          $this->openMode = self::OPEN_FOR_READ;
        }
        else {
          $this->openMode = self::OPEN_FOR_WRITE;
          $this->handle = tmpfile();
        }

        if ($options & STREAM_USE_PATH) {
          $opened_url = $uri;
        }
      }
    }

    if (!$success && !$error_reported && $options & STREAM_REPORT_ERRORS) {
      trigger_error('Cannot open stream', E_USER_WARNING);
    }

    return $success;
  }

  /**
   * Closes resource.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function stream_close() {
    return !($this->openMode & self::OPEN_FOR_WRITE) || fclose($this->handle);
  }

  /**
   * Advisory file locking.
   *
   * @param int $operation
   *   Locking operation.
   *
   * @return false
   *   Files locking is not supported.
   */
  public function stream_lock($operation) {
    return FALSE;
  }

  /**
   * Reads from stream.
   *
   * @param int $count
   *   How many bytes of data from the current position should be returned.
   *   (Usually 8Kb to add to buffer and then read from there).
   *
   * @return string|false
   *   Returns next part of file, or FALSE on failure.
   */
  public function stream_read($count) {
    if ($this->openMode & self::OPEN_FOR_READ) {
      try {
        if (!$this->buffer) {
          $buffer_size = \Drupal::config('yandexdisk.settings')->get('buffer_size');
          // With any result of reading from Disk the buffer array will contain
          // at least one empty string element.
          $this->buffer = str_split($this->disk->read($this->path, $this->position, $buffer_size), $count);
        }

        $data = array_shift($this->buffer);
        $this->position += strlen($data);
        return $data;
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e, 'Cannot read file @stream: @message', ['@stream' => $this->uri]);
      }
    }

    return FALSE;
  }

  /**
   * Writes to stream.
   *
   * @param string $data
   *   The data to write.
   *
   * @return int|false
   *   Number of bytes that were successfully stored.
   */
  public function stream_write($data) {
    if ($this->openMode & self::OPEN_FOR_WRITE) {
      fseek($this->handle, 0, SEEK_END);
      $length = (int) @fwrite($this->handle, $data);
      $this->position += $length;
      return $length;
    }

    return FALSE;
  }

  /**
   * Tests for end-of-file on a file pointer.
   *
   * @return bool
   *   Returns TRUE if the read position is at the end of the stream and if no
   *   more data is available to be read, or FALSE otherwise. Always returns
   *   TRUE if resource was open for writing.
   */
  public function stream_eof() {
    $properties = $this->disk->getProperties($this->path);
    return $this->position >= $properties['d:getcontentlength'];
  }

  /**
   * Seeks to specific location in a stream. Only for read mode.
   *
   * After calling this method this->position may differ from internal PHP's
   * buffer pointer. But it is OK as file stream behaves the same. An example is
   * in php.net docs.
   *
   * @param int $offset
   *   The stream offset to seek to.
   * @param int $whence
   *   Possible values: SEEK_SET, SEEK_CUR, SEEK_END.
   *
   * @return bool
   *   Returns TRUE if the position was updated, FALSE otherwise.
   *
   * @link http://php.net/manual/stream.streamwrapper.example-1.php
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    if ($this->openMode == self::OPEN_FOR_READ) {
      $properties = $this->disk->getProperties($this->path);

      switch ($whence) {
        case SEEK_SET:
          $new_position = $offset;
          break;

        case SEEK_CUR:
          $new_position = $offset + $this->position;
          break;

        case SEEK_END:
          $new_position = $offset + $properties['d:getcontentlength'];
          break;
      }

      if (isset($new_position)) {
        if ($new_position >= 0 && $new_position <= $properties['d:getcontentlength']) {
          $this->position = $new_position;
          $this->buffer = [];
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Retrieves the current position of a stream.
   *
   * @return int
   *   The current position.
   */
  public function stream_tell() {
    return $this->position;
  }

  /**
   * Puts a file to its place on disk if resource was opened for writing.
   *
   * @return bool
   *   TRUE if the cached data was successfully stored (or if there was no data
   *   to store), or FALSE if the data could not be stored.
   */
  public function stream_flush() {
    if ($this->openMode & self::OPEN_FOR_WRITE && $this->position) {
      fseek($this->handle, 0);
      $data = fread($this->handle, $this->position);

      // Get the possible data type by analyzing file extension. Do not supply
      // the full uri as it will try to search the realpath.
      $content_type = \Drupal::service('file.mime_type.guesser')->guess($this->path);

      try {
        $this->disk->write($this->path, $data, $content_type);
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e, 'Cannot write file @stream: @message', ['@stream' => $this->uri]);
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Retrieves information about a resource.
   *
   * @return array|null
   *   Stat array on success.
   */
  public function stream_stat() {
    try {
      $properties = $this->disk->getProperties($this->path);

      $stat_props = [
        'dev',
        'ino',
        'nlink',
        'uid',
        'gid',
        'rdev',
        'atime',
        'ctime',
      ];
      $stat = array_fill_keys($stat_props, 0);
      $stat['blksize'] = $stat['blocks'] = -1;
      $stat['size'] = (int) @$properties['d:getcontentlength'];
      $stat['mtime'] = strtotime($properties['d:getlastmodified']);
      // File/dir mode.
      $stat['mode'] = isset($properties['d:collection']) ? 16749 : 33206;

      return $stat;
    }
    catch (YandexDiskException $e) {
      watchdog_exception('yandexdisk', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    return $this->handle ? $this->handle : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_metadata($uri, $option, $value) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size) {
    return ftruncate($this->handle, $new_size);
  }

  /**
   * Retrieves information about a file or directory.
   *
   * @param string $uri
   *   The file URL to stat.
   * @param int $flags
   *   Additional flags set by the streams API. It can hold one or more of the
   *   following values OR'd together: STREAM_URL_STAT_LINK,
   *   STREAM_URL_STAT_QUIET.
   *
   * @return array|false|null
   *   Stat array on success.
   */
  public function url_stat($uri, $flags) {
    if ($this->setUri($uri) && $this->disk()) {
      try {
        if ($this->disk->pathExists($this->path)) {
          return $this->stream_stat();
        }
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e);
        return FALSE;
      }
    }

    if (~$flags & STREAM_URL_STAT_QUIET) {
      trigger_error('No such file or directory', E_USER_WARNING);
    }

    return FALSE;
  }

  /**
   * Deletes a file or directory.
   *
   * @param string $uri
   *   The file URL which should be deleted.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function unlink($uri) {
    $success = FALSE;

    if ($this->setUri($uri) && $this->disk()) {
      try {
        $success = $this->disk->delete($this->path)->execute();
      }
      catch (YandexDiskException $e) {
        watchdog_exception('yandexdisk', $e);
      }
    }

    return $success;
  }

  /**
   * Renames a file or directory.
   *
   * @param string $from_uri
   *   The URL to the current file.
   * @param string $to_uri
   *   The URL which the $from_uri should be renamed to.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function rename($from_uri, $to_uri) {
    $success = FALSE;
    $destination = new self();

    try {
      if ($this->setUri($from_uri) && $destination->setUri($to_uri) && $this->disk()) {
        if ($destination->user == $this->user) {
          // Use native way to move resources within a Disk.
          $success = $this->disk->move($this->path, $destination->path)->execute();
        }
        elseif ($this->disk->pathExists($this->path) && $destination->disk()) {
          $success = \Drupal::service('yandexdisk_manager')->copyRecursive($this->disk, $this->path, $destination->disk, $destination->path)
            && $this->unlink($from_uri);
        }
      }
    }
    catch (YandexDiskException $e) {
      watchdog_exception('yandexdisk', $e);
    }

    return $success;
  }

  /**
   * Creates a directory.
   *
   * @param string $uri
   *   Directory which should be created.
   * @param int $mode
   *   The value passed to mkdir().
   * @param int $options
   *   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE,
   *   STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function mkdir($uri, $mode, $options) {
    $success = FALSE;

    try {
      if ($this->setUri($uri) && ($disk = $this->disk()) && !$disk->isDir($this->path)) {
        $success = $disk->mkdir($this->path, $options & STREAM_MKDIR_RECURSIVE);
      }
    }
    catch (YandexDiskException $e) {
      watchdog_exception('yandexdisk', $e);
    }

    if (!$success && $options & STREAM_REPORT_ERRORS) {
      trigger_error('Cannot create directory', E_USER_WARNING);
    }

    return $success;
  }

  /**
   * Removes a directory. Always recursive.
   *
   * @param string $uri
   *   The directory URL which should be removed.
   * @param int $options
   *   A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE,
   *   STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see YandexDiskStreamWrapper::unlink()
   */
  public function rmdir($uri, $options) {
    if ($this->unlink($uri)) {
      return TRUE;
    }
    elseif ($options & STREAM_REPORT_ERRORS) {
      trigger_error('Cannot remove directory', E_USER_WARNING);
    }

    return FALSE;
  }

  /**
   * Opens directory handle.
   *
   * @param string $uri
   *   Specifies the URL that was passed to opendir().
   * @param int $options
   *   Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public function dir_opendir($uri, $options) {
    try {
      if ($this->setUri($uri) && $this->disk()) {
        $this->directoryContents = $this->disk->scanDir($this->path);
        $this->position = 0;
        return TRUE;
      }
    }
    catch (YandexDiskException $e) {
      watchdog_exception('yandexdisk', $e);
    }

    return FALSE;
  }

  /**
   * Reads entry from directory handle.
   *
   * @return string|false
   *   The next filename, or FALSE if there is no next file.
   */
  public function dir_readdir() {
    if (isset($this->directoryContents[$this->position])) {
      return $this->directoryContents[$this->position++];
    }

    return FALSE;
  }

  /**
   * Rewinds directory handle.
   *
   * @return true
   *   Always returns TRUE.
   */
  public function dir_rewinddir() {
    $this->position = 0;
    return TRUE;
  }

  /**
   * Closes directory handle.
   *
   * @return true
   *   Always returns TRUE.
   */
  public function dir_closedir() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @return string|false
   *   Public URL.
   */
  public function getExternalUrl() {
    if ($this->uri && $this->disk()) {
      $path = file_uri_target($this->uri);
      $url = Url::fromRoute('yandexdisk.file_download', [], ['absolute' => TRUE, 'query' => ['file' => $path]])->toString();
    }

    return !empty($url) ? $url : FALSE;
  }

  /**
   * Returns no real paths.
   *
   * @return false
   *   Realpath is not supported.
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri) || $this->setUri($uri)) {
      // Remove erroneous leading or trailing, forward-slashes and backslashes.
      $target = trim($this->path, '\/');

      $dirname = dirname($target);
      if ($dirname == '.') {
        $dirname = '';
      }

      return 'yandexdisk://' . $this->user . '/' . $dirname;
    }

    return FALSE;
  }

}
