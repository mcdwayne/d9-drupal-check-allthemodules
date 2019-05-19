<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\XHProfSample\CollectorInterface.
 */

namespace Drupal\xhprof_sample\XHProfSample;

use Symfony\Component\HttpFoundation\Request;

interface CollectorInterface {

  /**
   * Conditionally enable XHProf sampling.
   */
  public function enable();

  /**
   * Shutdown XHProf sampling.
   *
   * @return array
   *   Raw sample data.
   */
  public function shutdown();

  /**
   * Check whether XHProf sampling is enabled.
   *
   * @return bool
   *   True if XHProf sampling is enabled, false otherwise.
   */
  public function isEnabled();

  /**
   * Check to determine if sampling can be enabled for this request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return bool
   *   True if XHProf sampling can be enabled for the current request
   */
  public function canEnable(Request $request);

  /**
   * Determine if the necessary extension is loaded.
   *
   * @return bool
   *   True if the extension is loaded.
   */
  public function isLoaded();
}
