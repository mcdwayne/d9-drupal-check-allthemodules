<?php

namespace Drupal\phantomjs_capture;

use Drupal\Core\Url;

/**
 * Define an interface for a basic phantomjs helper.
 */
interface PhantomJSCaptureHelperInterface {

  /**
   * Return whether or not the binary exists at the
   * given path on the server.
   *
   * @param string $path
   * @return mixed
   */
  public function binaryExists($path);

  /**
   * Return the version of the phantomjs binary on the
   * server.
   *
   * @return mixed
   */
  public function getVersion();

  /**
   * Perform a capture with PhantomJS.
   *
   * @param $url
   * @param $destination
   * @param $filename
   * @param null $element
   * @return mixed
   */
  public function capture(Url $url, $destination, $filename, $element = NULL);

}