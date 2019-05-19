<?php

namespace Drupal\streamy\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Class DummyStreamWrapper
 *
 * This dummy stream wrapper is used to fake the SteamWrapperManager.
 * Every scheme registered with streamy will have a type StreamWrapperInterface::WRITE_VISIBLE
 * even if that scheme is not yet configured.
 * StreamWrapperManager doesn't support an update or a de-registration of a stream wrapper
 * so the only thing we can do is re-register the invalid stream wrapper
 * with the type StreamWrapperInterface::HIDDEN.
 *
 * @package Drupal\streamy\StreamWrapper
 */
class DummyStreamWrapper implements StreamWrapperInterface {

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   */
  public static function getType() {
    return StreamWrapperInterface::HIDDEN;
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName() {
    // Implement getName() method.
  }

  /**
   * Returns the description of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper description.
   */
  public function getDescription() {
    // Implement getDescription() method.
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
    // Implement setUri() method.
  }

  /**
   * Returns the stream resource URI.
   *
   * @return string
   *   Returns the current URI of the instance.
   */
  public function getUri() {
    // Implement getUri() method.
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
    // Implement getExternalUrl() method.
  }

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * @return string
   *   Returns a string with absolute pathname on success (implemented
   *   by core wrappers), or FALSE on failure or if the registered
   *   wrapper does not provide an implementation.
   */
  public function realpath() {
    // Implement realpath() method.
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
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    // Implement dirname() method.
  }

  /**
   * @return bool
   */
  public function dir_closedir() {
    // Implement dir_closedir() method.
  }

  /**
   * @return bool
   */
  public function dir_opendir($path, $options) {
    // Implement dir_opendir() method.
  }

  /**
   * @return string
   */
  public function dir_readdir() {
    // Implement dir_readdir() method.
  }

  /**
   * @return bool
   */
  public function dir_rewinddir() {
    // Implement dir_rewinddir() method.
  }

  /**
   * @return bool
   */
  public function mkdir($path, $mode, $options) {
    // Implement mkdir() method.
  }

  /**
   * @return bool
   */
  public function rename($path_from, $path_to) {
    // Implement rename() method.
  }

  /**
   * @return bool
   */
  public function rmdir($path, $options) {
    // Implement rmdir() method.
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
    // Implement stream_cast() method.
  }

  /**
   * Closes stream.
   */
  public function stream_close() {
    // Implement stream_close() method.
  }

  /**
   * @return bool
   */
  public function stream_eof() {
    // Implement stream_eof() method.
  }

  /**
   * @return bool
   */
  public function stream_flush() {
    // Implement stream_flush() method.
  }

  /**
   * @return bool
   */
  public function stream_lock($operation) {
    // Implement stream_lock() method.
  }

  /**
   * Sets metadata on the stream.
   *
   * @param string $path
   *     A string containing the URI to the file to set metadata on.
   * @param int    $option
   *     One of:
   *     - STREAM_META_TOUCH: The method was called in response to touch().
   *     - STREAM_META_OWNER_NAME: The method was called in response to chown()
   *     with string parameter.
   *     - STREAM_META_OWNER: The method was called in response to chown().
   *     - STREAM_META_GROUP_NAME: The method was called in response to chgrp().
   *     - STREAM_META_GROUP: The method was called in response to chgrp().
   *     - STREAM_META_ACCESS: The method was called in response to chmod().
   * @param mixed  $value
   *     If option is:
   *     - STREAM_META_TOUCH: Array consisting of two arguments of the touch()
   *     function.
   *     - STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner
   *     user/group as string.
   *     - STREAM_META_OWNER or STREAM_META_GROUP: The value of the owner
   *     user/group as integer.
   *     - STREAM_META_ACCESS: The argument of the chmod() as integer.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure. If $option is not
   *   implemented, FALSE should be returned.
   *
   * @see http://php.net/manual/streamwrapper.stream-metadata.php
   */
  public function stream_metadata($path, $option, $value) {
    // Implement stream_metadata() method.
  }

  /**
   * @return bool
   */
  public function stream_open($path, $mode, $options, &$opened_path) {
    // Implement stream_open() method.
  }

  /**
   * @return string
   */
  public function stream_read($count) {
    // Implement stream_read() method.
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
    // Implement stream_seek() method.
  }

  /**
   * Change stream options.
   *
   * This method is called to set options on the stream.
   *
   * @param int $option
   *     One of:
   *     - STREAM_OPTION_BLOCKING: The method was called in response to
   *     stream_set_blocking().
   *     - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
   *     stream_set_timeout().
   *     - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
   *     stream_set_write_buffer().
   * @param int $arg1
   *     If option is:
   *     - STREAM_OPTION_BLOCKING: The requested blocking mode:
   *     - 1 means blocking.
   *     - 0 means not blocking.
   *     - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
   *     - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
   *     STREAM_BUFFER_FULL.
   * @param int $arg2
   *     If option is:
   *     - STREAM_OPTION_BLOCKING: This option is not set.
   *     - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
   *     - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
   *   should be returned.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    // Implement stream_set_option() method.
  }

  /**
   * @return array
   */
  public function stream_stat() {
    // Implement stream_stat() method.
  }

  /**
   * @return int
   */
  public function stream_tell() {
    // Implement stream_tell() method.
  }

  /**
   * Truncate stream.
   *
   * Will respond to truncation; e.g., through ftruncate().
   *
   * @param int $new_size
   *   The new size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function stream_truncate($new_size) {
    // Implement stream_truncate() method.
  }

  /**
   * @return int
   */
  public function stream_write($data) {
    // Implement stream_write() method.
  }

  /**
   * @return bool
   */
  public function unlink($path) {
    // Implement unlink() method.
  }

  /**
   * @return array
   */
  public function url_stat($path, $flags) {
    // Implement url_stat() method.
  }
}