<?php

namespace Drupal\git_info;

/**
 * Interface GitInfoInterface.
 *
 * @package Drupal\git_info
 */
interface GitInfoInterface {

  public function getShortHash();

  public function getVersion();

  public function getDate();

  public function getApplicationVersionString();

}
