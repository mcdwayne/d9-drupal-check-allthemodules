<?php

namespace Drupal\npm\Plugin;

/**
 * Interface definition for node package manager plugins.
 */
interface NpmExecutableInterface {

  /**
   * Returns true if given plugin can be used (executable is available).
   *
   * @return bool
   */
  public function isAvailable();

  /**
   * Writes an empty package.json file.
   *
   * @return \Symfony\Component\Process\Process
   *
   * @throws \Drupal\npm\Exception\NpmCommandFailedException
   */
  public function initPackageJson();

  /**
   * Requires given packages.
   *
   * @param String[] $packages
   *   An array of packages to require.
   * @param string $type
   *   Type of dependencies. One of ('prod', 'dev', 'optional').
   * @return \Symfony\Component\Process\Process
   *
   * @throws \Drupal\npm\Exception\NpmCommandFailedException
   * @throws \RuntimeException
   */
  public function addPackages($packages, $type = 'prod');

  /**
   * Executes a script.
   *
   * @param array $args
   *   An array of arguments starting with the script name.
   * @param callable|NULL $callback
   *   Callback to pass to \Symfony\Component\Process\Process::wait().
   * @param int|NULL $timeout
   *   Timeout in seconds.
   *
   * @return \Symfony\Component\Process\Process
   * @throws \Drupal\npm\Exception\NpmCommandFailedException
   */
  public function runScript($args, callable $callback = NULL, $timeout = NULL);

}
