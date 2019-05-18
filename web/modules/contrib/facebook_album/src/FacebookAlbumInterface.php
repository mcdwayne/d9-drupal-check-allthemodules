<?php
/**
 * @file
 * Contains \Drupal\facebook_album\FacebookAlbumInterface.
 */

namespace Drupal\facebook_album;

/**
 * FacebookAlbumInterface.
 */
interface FacebookAlbumInterface {

  /**
   * Get response data
   *
   * @param string $call_path
   * @param array $parameters
   *
   * @return mixed
   */
  public function get($call_path = '', $parameters = array());

  /**
   * Translate API errors into a user friendly error.
   *
   * @param $code
   *    The error code returned from the facebook API or internally
   * @param $message
   *    The corresponding message to that error code, if there is one
   * @return string
   *    A user friendly error message
   */
  public function translate_error($code, $message);

}
