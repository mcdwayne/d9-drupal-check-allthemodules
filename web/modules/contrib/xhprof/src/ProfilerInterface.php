<?php

namespace Drupal\xhprof;

use Symfony\Component\HttpFoundation\Request;

interface ProfilerInterface {

  /**
   * Conditionally enable XHProf profiling.
   */
  public function enable();

  /**
   * Shutdown and disable XHProf profiling.
   * Report is saved with selected storage.
   *
   * @return array
   */
  public function shutdown($runId);

  /**
   * Check whether XHProf is enabled.
   *
   * @return boolean
   */
  public function isEnabled();

  /**
   * Return true if XHProf profiling can be
   * enabled for the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  public function canEnable(Request $request);

  /**
   * @return bool
   */
  public function isLoaded();

  /**
   * @return array
   */
  public function getExtensions();

  /**
   * Generates a link to the report page for a specific run ID.
   *
   * @param string $run_id
   *
   * @return string
   */
  public function link($run_id);

  /**
   * Return the current selected
   * storage.
   *
   * @return \Drupal\xhprof\XHProfLib\Storage\StorageInterface
   */
  public function getStorage();

  /**
   * Return the run id associated
   * with the current request.
   *
   * @return string
   */
  public function getRunId();

  /**
   * Create a new unique run id.
   *
   * @return string
   */
  public function createRunId();

  /**
   * Load a specific run.
   *
   * @param string $run_id
   *
   * @return \Drupal\xhprof\XHProfLib\Run
   */
  public function getRun($run_id);
}
