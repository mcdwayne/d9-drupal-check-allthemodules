<?php

namespace Drupal\rokka\RokkaAdapter;

use Drupal\rokka\Entity\RokkaMetadata;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Stream;
use Rokka\Client\Core\SourceImage;
use Rokka\Client\Image;

/**
 *
 */
abstract class StreamWrapper {

  public static $supportedModes = ['w', 'r'];

  /**
   * @var \Rokka\Client\Image
   */
  protected static $imageClient;

  /**
   * @var \GuzzleHttp\Psr7\Stream
   */
  protected $body;

  /**
   * @var string
   */
  protected $uri;

  /**
   * @var string
   */
  protected $mode;

  /**
   * @param \Rokka\Client\Image $imageClient
   */
  public function __construct(Image $imageClient) {
    // If (!static::$bodies) {
    //      static::$bodies = [];
    //    }.
    static::$imageClient = $imageClient;

  }

  /**
   * Support for stat().
   *
   * This important function goes back to the Unix way of doing things.
   * In this example almost the entire stat array is irrelevant, but the
   * mode is very important. It tells PHP whether we have a file or a
   * directory and what the permissions are. All that is packed up in a
   * bitmask. This is not normal PHP fodder.
   *
   * @param string $uri
   *   A string containing the URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *
   * @return array|bool
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   *   Use the formatUrlStat() function as an helper for return values.
   *
   * @see http://php.net/manual/en/streamwrapper.url-stat.php
   */
  abstract public function url_stat($uri, $flags);

  /**
   * Implements getUri().
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Implements setUri().
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Close the stream.
   */
  public function stream_close() {
    if ($this->body) {
      $this->body->close();
      $this->body = NULL;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param string $path
   * @param string $mode
   * @param array $options
   * @param string $opened_path
   *
   * @return bool
   */
  public function stream_open($path, $mode, $options, &$opened_path) {
    $this->uri = $path;

    // We don't care about the binary flag.
    $this->mode = rtrim($mode, 'bt');

    // $this->params = $params = $this->getParams($path);
    $exceptions = [];
    if (strpos($this->mode, '+')) {
      $exceptions[] = new \LogicException('The RokkaStreamWrapper does not support simultaneous reading and writing (mode: {' . $this->mode . '}).');
    }
    if (!in_array($this->mode, static::$supportedModes)) {
      $exceptions[] = new \LogicException('Mode not supported: {' . $this->mode . '}. Use one "r", "w".', 400);
    }

    $ret = NULL;
    if (empty($exceptions)) {
      // This stream is Write-Only since the stream is not reversible for Read
      // and Write operations from the same filename: to read from a previously
      // written filename, the HASH must be provided.
      if ('w' == $this->mode) {
        $ret = $this->openWriteStream($options, $exceptions);
      }

      if ('r' == $this->mode) {
        $ret = $this->openReadStream($options, $exceptions);
      }
    }

    if (!empty($exceptions)) {
      return $this->triggerException($exceptions);
    }

    $ret = TRUE;
    return $ret;
  }

  /**
   * Initialize the stream wrapper for a write only stream.
   *
   * @param array $params
   *   Operation parameters.
   * @param array $errors
   *   Any encountered errors to append to.
   *
   * @return bool
   */
  protected function openWriteStream($params, &$errors) {
    // We must check HERE if the underlying connection to Rokka is working fine
    // instead of returning FALSE during stream_flush() and stream_close() if
    // Rokka service is not available.
    // Reason: The PHP core, in the "_php_stream_copy_to_stream_ex()" function, is
    // not checking if the stream contents got successfully written after the
    // source and destination streams have been opened.
    try {
      // @todo: Using listStack() invocation to check if Rokka is still alive,
      // but we must use a better API invocation here!
      self::$imageClient->listStacks(1);
      $this->body = new Stream(fopen('php://temp', 'r+'));
      return TRUE;
    } catch (\Exception $e) {
      $errors[] = $e;
      return $this->triggerException($errors);
    }
  }

  /**
   * Trigger one or more errors.
   *
   * @param \Exception|\Exception[] $exceptions
   * @param mixed $flags
   *   If set to STREAM_URL_STAT_QUIET, then no error or
   *   exception occurs.
   *
   * @return bool
   */
  protected function triggerException($exceptions, $flags = NULL) {
    if ($flags & STREAM_URL_STAT_QUIET) {
      // This is triggered with things like file_exists()
      if ($flags & STREAM_URL_STAT_LINK) {
        // This is triggered for things like is_link()
        // return $this->formatUrlStat(false);
      }
      return FALSE;
    }

    $exceptions = is_array($exceptions) ? $exceptions : [$exceptions];
    $messages = [];
    /** @var \Exception $exception */
    foreach ($exceptions as $exception) {
      $messages[] = $exception->getMessage();
    }

    trigger_error(implode("\n", $messages), E_USER_WARNING);
    return FALSE;
  }

  /**
   * Initialize the stream wrapper for a read only stream.
   *
   * @param array $params
   *   Operation parameters.
   * @param array $errors
   *   Any encountered errors to append to.
   *
   * @return bool
   */
  protected function openReadStream($params, &$errors) {
    $meta = $this->doGetMetadataFromUri($this->uri);
    if (empty($meta)) {
      $errors[] = new \LogicException('Unable to determine the Rokka.io HASH for the current URI.', 404);
      return $this->triggerException($errors);
    }

    try {
      // Load the binary data directly from the source image or the image derivate.
      $file_url = $this->getExternalUrl($this->uri);
      $sourceStream = fopen($file_url, 'r');
      $this->body = new Stream($sourceStream, 'rb');

      // Wrap the body in a caching entity body if seeking is allowed.
      if (!$this->body->isSeekable()) {
        $this->body = new CachingStream($this->body);
      }
    } catch (\Exception $e) {
      $errors[] = $e;
      return $this->triggerException($errors);
    }
    return TRUE;
  }

  /**
   * @param $uri
   *
   * @return \Rokka\Client\Core\SourceImageMetadata
   */
  abstract protected function doGetMetadataFromUri($uri);

  /**
   * Write data the to the stream.
   *
   * @param string $data
   *
   * @return int Returns the number of bytes written to the stream
   */
  public function stream_write($data) {
    return $this->body->write($data);
  }

  /**
   * @return bool
   */
  public function stream_eof() {
    return $this->body->eof();
  }

  /**
   * Support for ftell().
   *
   * @return int
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-tell.php
   */
  public function stream_tell() {
    return $this->body->tell();
  }

  /**
   * Support for fflush().
   *
   * @return bool
   *   TRUE if data was successfully stored (or there was no data to store).
   */
  public function stream_flush() {
    if ('r' == $this->mode) {
      // Read only Streams can not be flushed, just return true.
      return TRUE;
    }
    $this->body->rewind();
    try {
      $imageCollection = static::$imageClient->uploadSourceImage(
        $this->body->getContents(),
        basename($this->uri)
      );

      if (1 !== $imageCollection->count()) {
        $exception = new \LogicException('RokkaStreamWrapper: No SourceImage data returned after invoking uploadSourceImage()!', 404);
        return $this->triggerException($exception);
      }

      /** @var \Rokka\Client\Core\SourceImage $image */
      $image = reset($imageCollection->getSourceImages());
      $image->size = $this->body->getSize();

      // Invoking Post-Save callback.
      return $this->doPostSourceImageSaved($image);
    } catch (\Exception $e) {
      $this->body = NULL;
      return $this->triggerException($e);
    }
  }

  /**
   * @param \Rokka\Client\Core\SourceImage $sourceImage
   *
   * @return bool
   */
  abstract protected function doPostSourceImageSaved(SourceImage $sourceImage);

  /**
   * Support for fstat().
   *
   * @return array
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  public function stream_stat() {
    return [
      'size' => $this->body->getSize(),
    ];
  }

  /**
   * Clean the Drupal image style url schema.
   *
   * @param $uri
   *
   * @return mixed
   */
  public function sanitizeUri($uri) {
    $uri = preg_replace('&styles/.*/rokka/&', '', $uri);

    return $uri;
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $meta = $this->doGetMetadataFromUri($uri);

    if (!$meta || empty($meta->getHash())) {
      $exception = new \LogicException('Unable to determine the Rokka.io HASH for the current URI.', 404);
      return $this->triggerException($exception);
    }
    try {
      return self::$imageClient->deleteSourceImage($meta->getHash())
        && $this->doPostSourceImageDeleted($meta);
    } catch (\Exception $e) {
      return $this->triggerException($e, STREAM_URL_STAT_QUIET);
    }
  }

  /**
   * @param \Drupal\rokka\Entity\RokkaMetadata $meta
   *
   * @return bool
   */
  abstract protected function doPostSourceImageDeleted(RokkaMetadata $meta);

  /**
   * Support for flock().
   *
   * The Rokka.io service has no locking capability, so return TRUE.
   *
   * @return bool
   *   Always returns TRUE at the present time. (not supported)
   */
  public function stream_lock($operation) {
    return TRUE;
  }

  /**
   * Read data from the underlying stream.
   *
   * @param int $count
   *   Amount of bytes to read.
   *
   * @return string
   *   Always returns FALSE. (not supported)
   */
  public function stream_read($count) {
    if ('r' == $this->mode) {
      return $this->body->read($count);
    }
    return FALSE;
  }

  /**
   * Seek to a specific byte in the stream.
   *
   * @param int $offset
   *   Seek offset.
   * @param int $whence
   *   Whence (SEEK_SET, SEEK_CUR, SEEK_END)
   *
   * @return bool
   *   Always returns FALSE. (not supported)
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    if ($this->body->isSeekable()) {
      $this->body->seek($offset, $whence);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Rokka.io has no support for chmod().
   *
   * @param string $mode
   *   A string containing the new mode for the resource.
   *
   * @return bool
   *   TRUE if resource permissions were successfully modified.
   *
   *   Always returns TRUE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.chmod.php
   */
  public function chmod($mode) {
    return TRUE;
  }

  /**
   * Helper function to prepare a url_stat result array.
   * All files and folders will be returned with 0777 permission.
   *
   * @param string|array $result
   *   Data to add
   *   - Null or String for Folders
   *   - Array for Files with the following keyed values:
   *    - 'timestamp': the creation/modification timestamp
   *    - 'filesize': the file dimensions.
   *
   * @return array Returns the modified url_stat result
   */
  protected function formatUrlStat($result = NULL) {
    static $statTemplate = [
      0 => 0,
      'dev' => 0,
      1 => 0,
      'ino' => 0,
      2 => 0,
      'mode' => 0,
      3 => 0,
      'nlink' => 0,
      4 => 0,
      'uid' => 0,
      5 => 0,
      'gid' => 0,
      6 => -1,
      'rdev' => -1,
      7 => 0,
      'size' => 0,
      8 => 0,
      'atime' => 0,
      9 => 0,
      'mtime' => 0,
      10 => 0,
      'ctime' => 0,
      11 => -1,
      'blksize' => -1,
      12 => -1,
      'blocks' => -1,
    ];
    $stat = $statTemplate;
    $type = gettype($result);
    // Determine what type of data is being cached.
    if ($type == 'NULL' || $type == 'string') {
      // Directory with 0777 access - see "man 2 stat".
      $stat['mode'] = $stat[2] = 0040777;
    }
    elseif ($type == 'array' && isset($result['timestamp'])) {
      // ListObjects or HeadObject result.
      $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10] = $result['timestamp'];
      // $stat['atime'] = $stat[8] = $result['timestamp'];.
      $stat['size'] = $stat[7] = $result['filesize'];
      // Regular file with 0777 access - see "man 2 stat".
      $stat['mode'] = $stat[2] = 0100777;
    }
    return $stat;
  }

}
