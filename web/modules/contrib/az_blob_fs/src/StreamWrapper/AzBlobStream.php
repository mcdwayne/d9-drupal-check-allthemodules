<?php

namespace Drupal\az_blob_fs\StreamWrapper;

use ArrayObject;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Exception;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use MicrosoftAzure\Storage\Blob\Models\Block;
use MicrosoftAzure\Storage\Blob\Models\BlockList;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Psr\Http\Message\StreamInterface;
use Drupal\Component\Utility\UrlHelper;


/**
 * Class AzBlobStream.
 */
class AzBlobStream implements StreamWrapperInterface, StreamInterface {

  use StreamDecoratorTrait;

  use StringTranslationTrait;

  /**
   * Module configuration for stream.
   *
   * @var array
   */
  private $config = [];

  /**
   * Microsoft Blob client
   *
   * @var \MicrosoftAzure\Storage\Blob\BlobRestProxy
   */
  private $client = NULL;

  /**
   * The Azure Blob Drupal Service
   *
   * @var \Drupal\az_blob_fs\AzBlobService
   */
  private $azBlob = NULL;

  /**
   * Mode in which the stream was opened
   *
   * @var string
   */
  private $mode;

  /**
   * The Azure Storage blob container
   *
   * @var string
   */
  private $container;

  /**
   * Instance uri referenced as "<scheme>://key".
   *
   * @var string
   */
  protected $uri = NULL;

  /**
   * Directory listing used by the dir_* methods.
   *
   * @var array
   */
  protected $dir = NULL;

  private $iterator;

  /**
   * Temporary file handle
   *
   * @var resource
   */
  protected $temporaryFileHandle = null;


  /**
   * Constructs a new AzBlobStream object.
   */
  public function __construct() {
    // Dependency injection will not work here, since stream wrappers
    // are not loaded the normal way: PHP creates them automatically
    // when certain file functions are called.  This prevents us from
    // passing arguments to the constructor, which we'd need to do in
    // order to use standard dependency injection as is typically done
    // in Drupal 8.

    $settings = &drupal_static('AzBlobFs_settings_available');
    if ($settings !== NULL) {
      $this->config = $settings['config'];
      return;
    }

    $config = \Drupal::config('az_blob_fs.settings');
    foreach ($config->get() as $prop => $value) {
      $this->config[$prop] = $value;
    }

    if ($this->config['az_blob_account_name'] == ''
      || $this->config['az_blob_account_key'] == '') {
      return;
    }
    $this->container = $this->config['az_blob_container_name'];
    $this->azBlob = \Drupal::service('az_blob');
    $this->client = $this->getClient();
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->azBlob->getAzBlobProxyClient($this->config);
  }

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName() {
    return $this->t('Azure Storage Blob File System');
  }

  /**
   * Returns the description of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper description.
   */
  public function getDescription() {
    return $this->t('Azure Storage Blob File System');
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
   * Sets the absolute stream resource URI.
   *
   * This allows you to set the URI. Generally is only called by the factory
   * method.
   *
   * @param string $uri
   *   A string containing the URI that should be used for this instance.
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Returns the stream resource URI.
   *
   * @return string
   *   Returns the current URI of the instance.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Returns a web accessible URL for the resource.
   *
   * This function should return a URL that can be embedded in a web page
   * and accessed from a browser. For example, the external URL of
   * "youtube://xIpLd0WQKCY" might be
   * "http://www.youtube.com/watch?v=xIpLd0WQKCY".
   *
   * @return string
   *   Returns a string containing a web accessible URL for the resource.
   */
  public function getExternalUrl() {
    // Get the target destination without the scheme.
    $target = file_uri_target($this->uri);

    // If there is no target we won't return path to the bucket,
    // instead we'll return empty string.
    if (empty($target)) {
        return '';
    }

    return $this->client->_getBlobUrl($this->container, $target);
  }

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * * This wrapper does not support realpath().
   *
   * @return bool
   *   Always returns FALSE.
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * Extract container name
   *
   * @param string $path
   * @return string
   */
  protected function getContainerName($path) {
    $url = parse_url($path);
    if ($url['host']) {
      return $url['host'];
    }
    return '';
  }
  /**
   * Extract file name
   *
   * @param string $path
   * @return string
   */
  protected function getFileName($path) {
    $url = parse_url($path);
    if ($url['host']) {
      $fileName = isset($url['path']) ? $url['path'] : $url['host'];
      if (strpos($fileName, '/') === 0) {
        $fileName = substr($fileName, 1);
      }
      return $fileName;
    }
    return '';
  }

  /**
   * Close the directory listing handles
   *
   * @return bool
   */
  public function dir_closedir() {
    $this->iterator = NULL;

    return TRUE;
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
  public function dir_opendir($uri, $options) {
    if ($this->client->uriIsFile($uri)) {
      // Path is a file but return TRUE without creating the iterator.
      return TRUE;
    }

    $prefix = $uri;
    if ($uri == '/' || $uri == '') {
      $prefix = '';
    }
    else {
      // Add trailing slash
      if(substr($uri, -1) != '/') {
        $prefix = $uri . '/';
      }
    }

    $options = new ListBlobsOptions();
    $options->setPrefix($prefix);
    $options->setDelimiter('/');
    $blobs_result = $this->client->listBlobs($this->container, $options);

    $blobs = new ArrayObject($blobs_result->getBlobs());
    if (empty($blobs)) {
      $this->dir_closedir();
      return FALSE;
    }
    $this->iterator = $blobs->getIterator();

    return TRUE;
  }

  /**
   * This method is called in response to readdir()
   *
   * @return string Should return a string representing the next filename, or
   *                false if there is no next file.
   * @link http://www.php.net/manual/en/function.readdir.php
   */
  public function dir_readdir() {
    // Skip empty result keys
    if (!$this->iterator->valid()) {
      return false;
    }

    $file_name = $this->iterator->valid() ? $this->iterator->current()->getName() : FALSE;
    $this->iterator->next();

    // The blobs hold their names as the full path of the namespace
    // we want the actual file name.
    if ($file_name) {
      $file_name = explode('/', $file_name);
      return end($file_name);
    }
    return FALSE;
  }

  /**
   * @return bool
   */
  public function dir_rewinddir() {
    // Skip empty result keys
    if (empty($this->iterator)) {
      return TRUE;
    }
    try {
      $this->iterator->rewind();
      return TRUE;
    }
    catch (ServiceException $e) {
      watchdog_exception('Azure Blob File System', $e);
      return FALSE;
    }
  }

  /**
   * Azure Blob Storage doesn't support physical directories so always return TRUE
   *
   * @return bool
   */
  public function mkdir($path, $mode, $options) {
    return TRUE;
  }

  /**
   * Called in response to rename() to rename a file or directory. Currently
   * only supports renaming objects.
   *
   * @param string $path_from the path to the file to rename
   * @param string $path_to   the new path to the file
   *
   * @return bool true if file was successfully renamed
   * @link http://www.php.net/manual/en/function.rename.php
   */
  public function rename($path_from, $path_to) {
    return $this->client->renameBlob($this->container, $path_from, $this->container, $path_to);
  }

  /**
   * @return bool
   */
  public function rmdir($uri, $options) {
    return $this->unlink($uri);
  }

  /**
   * Retrieve the underlying stream resource.
   *
   * This method is called in response to stream_select().
   *
   * @param int $cast_as
   *   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling
   *   stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for
   *   other uses.
   *
   * @return resource|false
   *   The underlying stream resource or FALSE if stream_select() is not
   *   supported.
   *
   * @see stream_select()
   * @see http://php.net/manual/streamwrapper.stream-cast.php
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * Closes stream.
   */
  public function stream_close() {
    $this->stream = $this->cache = null;
  }

  /**
   * @return bool
   */
  public function stream_eof() {
    return $this->eof();
  }

  /**
   * @return bool
   *
   */
  public function stream_flush() {
    if ($this->mode == 'r') {
      return FALSE;
    }

    if ($this->isSeekable()) {
      $this->seek(0);
    }

    try {
      $block_id = base64_encode(basename($this->uri));
      $blocks = [new Block($block_id, 'Uncommitted')];
      $blocksList = BlockList::create($blocks);

      $blob_name = file_uri_target($this->uri);

      $this->client->createBlobBlock($this->container, $blob_name, $block_id, $this->stream);
      $this->client->commitBlobBlocks($this->container, $blob_name, $blocksList);

      $this->stream = '';
      $this->iterator = FALSE;

      return TRUE;
    }
    catch (ServiceException $e) {
      watchdog_exception('Azure Blob File System 1', $e);
      return FALSE;
    }
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
   * Sets metadata on the stream.
   *
   * @param string $path
   *   A string containing the URI to the file to set metadata on.
   * @param int $option
   *   One of:
   *   - STREAM_META_TOUCH: The method was called in response to touch().
   *   - STREAM_META_OWNER_NAME: The method was called in response to chown()
   *     with string parameter.
   *   - STREAM_META_OWNER: The method was called in response to chown().
   *   - STREAM_META_GROUP_NAME: The method was called in response to chgrp().
   *   - STREAM_META_GROUP: The method was called in response to chgrp().
   *   - STREAM_META_ACCESS: The method was called in response to chmod().
   * @param mixed $value
   *   If option is:
   *   - STREAM_META_TOUCH: Array consisting of two arguments of the touch()
   *     function.
   *   - STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner
   *     user/group as string.
   *   - STREAM_META_OWNER or STREAM_META_GROUP: The value of the owner
   *     user/group as integer.
   *   - STREAM_META_ACCESS: The argument of the chmod() as integer.
   *
   *    * This wrapper does not support touch(), chmod(), chown(), or chgrp().
   *
   * Manual recommends return FALSE for not implemented options, but Drupal
   * require TRUE in some cases like chmod for avoid watchdog erros.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-metadata.php
   * @see \Drupal\Core\File\FileSystem::chmod()
   *
   * Returns FALSE if the option is not included in bypassed_options array
   * otherwise, TRUE.
   *
   * @return bool
   *
   * @see http://php.net/manual/streamwrapper.stream-metadata.php
   */
  public function stream_metadata($path, $option, $value) {
    $bypassed_options = [STREAM_META_ACCESS];
    return in_array($option, $bypassed_options);
  }

  /**
   * Opens a stream, as for fopen(), file_get_contents(), file_put_contents().
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   * @param string $mode
   *   The file mode ("r", "wb" etc.).
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string &$opened_path
   *   A string containing the path actually opened.
   *
   * @return bool
   *   Returns TRUE if file was opened successfully. (Always returns TRUE).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->setUri($uri);
    $this->stream = new Stream(fopen('php://temp', $mode));
    // Get the target destination without the scheme.
    $target = file_uri_target($this->uri);

    try {
      $this->client->getBlob($this->container, $target);
    }
    catch (ServiceException $e) {
      //return FALSE;
    }

    if ($mode == 'rb') {
      $this->temporaryFileHandle = $this
        ->client->getBlob($this->container, $target)
        ->getContentStream();
    }

    return TRUE;
  }

  /**
   * @return string
   */
  public function stream_read($count) {
    return fread($this->temporaryFileHandle, $count);
  }

  /**
   * Seeks to specific location in a stream.
   *
   * This method is called in response to fseek().
   *
   * The read/write position of the stream should be updated according to the
   * offset and whence.
   *
   * @param int $offset
   *   The byte offset to seek to.
   * @param int $whence
   *   Possible values:
   *   - SEEK_SET: Set position equal to offset bytes.
   *   - SEEK_CUR: Set position to current location plus offset.
   *   - SEEK_END: Set position to end-of-file plus offset.
   *   Defaults to SEEK_SET.
   *
   * @return bool
   *   TRUE if the position was updated, FALSE otherwise.
   *
   * @see http://php.net/manual/streamwrapper.stream-seek.php
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    if ($this->isSeekable()) {
      $this->seek($offset, $whence);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Change stream options.
   *
   * This method is called to set options on the stream.
   *
   * @param int $option
   *   One of:
   *   - STREAM_OPTION_BLOCKING: The method was called in response to
   *     stream_set_blocking().
   *   - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
   *     stream_set_timeout().
   *   - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
   *     stream_set_write_buffer().
   * @param int $arg1
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: The requested blocking mode:
   *     - 1 means blocking.
   *     - 0 means not blocking.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
   *     STREAM_BUFFER_FULL.
   * @param int $arg2
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: This option is not set.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
   *   should be returned.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return FALSE;
  }

  /**
   * @return array
   */
  public function stream_stat() {
    return $this->url_stat($this->uri, 0);
  }

  /**
   * @return int
   */
  public function stream_tell() {
    return $this->tell();
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
   * @param $data
   *
   * @return int
   */
  public function stream_write($data) {
    return $this->write($data);
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    // Get the target destination without the scheme.
    $target = file_uri_target($uri);
    try {
      $del = $this->client->deleteBlob($this->container, $target);
    }
    catch (ServiceException $e) {
      $del = false;
    }

    return $del;
  }

  /**
   * @return array
   */
  public function url_stat($uri, $flags) {
    // Use static cache to prevent multiple requests to backend.
    //$cache = &drupal_static(__FUNCTION__);
    //static $cache = [];
    //if (!isset($cache[$uri])) {
      // @see http://be2.php.net/manual/en/function.stat.php
      $stat = array_fill_keys([
        'dev',
        'ino',
        'mode',
        'nlink',
        'uid',
        'gid',
        'rdev',
        'size',
        'atime',
        'mtime',
        'ctime',
        'blksize',
        'blocks'
      ], 0);

      // If $blob_prefixes is not empty and $blobs is, it means it's a directory
      // If $blobs is not empty and $blob_prefixes is, it's a file
      // If both are empty, the blob does not exist
      //$blob_list = $this->client->getPrefixedBlob($this->container, $uri);
    // Get the target destination without the scheme.
    $target = file_uri_target($uri);
      $blob = $this->client->getPrefixedBlob($this->container, $target);

      $blob_prefixes = [];
      $pathArray = explode("/", $target);
      $count=0;
      foreach ($pathArray as $key => $slice) {
        $pos = strpos($slice, '.');
        $count++;
        if ($pos !== false) {
          $blob_prefixes[] = $slice;
        }
      }

/*      try {
        $result = $this->client->getBlobMetadata($this->container, $parts[1]);
        $retMetadata = $result->getMetadata();
        foreach ($retMetadata as $key => $value) {
          $metadata[$key] = $value;
        }
      }
      catch (ServiceException $e) {
        watchdog_exception('Azure Blob File System', $e);
      }*/

      //}

      //$blob_prefixes = [];
      //$blob_prefixes = $blob->getBlobPrefixes();
      //$blobs = $blob_list->getBlobs();

      // Blob exists
      //if (!empty($blob) && $t!='') {
        // Blob is a file
        if (!empty($blob) && !empty($blob_prefixes)) {
          //$blob = reset($blobs);
          $blob_properties = $blob->getProperties();
          // Use the S_IFREG posix flag for files.
          // All files are considered writable, so OR in 0777.
          $stat['mode'] = 0100000 | 0777;
          $stat['size'] = $blob_properties->getContentLength();
          $stat['mtime'] = date_timestamp_get($blob_properties->getLastModified());
          $stat['blksize'] = -1;
          $stat['blocks'] = -1;
          $cache[$uri] = $stat;
        }


        if (empty($blob)) {
          // Blob is directory
          if (empty($blob_prefixes)) {
            // Use the S_IFDIR posix flag for directories
            // All directories are considered writable, so OR in 0777.
            $stat['mode'] = 0040000 | 0777;
            $cache[$uri] = $stat;
          }
          else {
            $cache[$uri] = FALSE;
          }
        }


     /* }
      else {
        // Blob doesn't exit
        $cache[$uri] = FALSE;
      }*/

      return $cache[$uri];
    //}
  }

  /**
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the normal PHP dirname() function, which does not support stream
   * wrappers.
   *
   * @param string $uri
   *   An optional URI.
   *
   * @return string
   *   A string containing the directory name, or FALSE if not applicable.
   *
   * @see \Drupal::service('file_system')->dirname()
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $fs = \Drupal::service('file_system');
    $scheme = $fs->uriScheme($uri);
    $dirname = $fs->dirname((file_uri_target($uri)));

    // When the dirname() call above is given '$scheme://', it returns '.'.
    // But '$scheme://.' is an invalid uri, so we return "$scheme://" instead.
    if ($dirname == '.') {
      $dirname = '';
    }

    return "$scheme://$dirname";
  }
}
