<?php

namespace Drupal\drd\Update;

use Drupal\drd\Entity\CoreInterface;

/**
 * Defines the required interface for all DRD Update Storage plugins.
 */
interface PluginStorageInterface extends PluginInterface {

  /**
   * Set the plugins for the subsequent update steps.
   *
   * @param PluginBuildInterface $build
   *   The build plugin.
   * @param PluginProcessInterface $process
   *   The process plugin.
   * @param PluginTestInterface $test
   *   The test plugin.
   * @param PluginDeployInterface $deploy
   *   The deploy plugin.
   * @param PluginFinishInterface $finish
   *   The finish plugin.
   *
   * @return $this
   */
  public function stepPlugins(
    PluginBuildInterface $build,
    PluginProcessInterface $process,
    PluginTestInterface $test,
    PluginDeployInterface $deploy,
    PluginFinishInterface $finish);

  /**
   * Start the update process.
   *
   * @param \Drupal\drd\Entity\CoreInterface $core
   *   The core entity to update.
   * @param \Drupal\drd\Entity\ReleaseInterface[] $releases
   *   The list of releases that require updates.
   * @param bool $dry
   *   Whether to run the update in dry mode.
   * @param bool $showlog
   *   Whether to display the log afterwards.
   *
   * @return bool|string
   *   TRUE if the process finished successfully or an error message otherwise.
   */
  public function execute(CoreInterface $core, array $releases, $dry, $showlog);

  /**
   * Append item(s) to the log.
   *
   * @param string|string[] $log
   *   The log item(s).
   *
   * @return $this
   */
  public function log($log);

  /**
   * Get the core entity.
   *
   * @return \Drupal\drd\Entity\CoreInterface
   *   The core entity.
   */
  public function getCore();

  /**
   * Get the Drupal root directory.
   *
   * @return string
   *   The Drupal root directory.
   */
  public function getDrupalDirectory();

  /**
   * Get the project's working directory.
   *
   * @return string
   *   The project's working directory.
   */
  public function getWorkingDirectory();

  /**
   * Get the build plugin.
   *
   * @return PluginBuildInterface
   *   The build plugin.
   */
  public function getBuildPlugin();

  /**
   * Get the process plugin.
   *
   * @return PluginProcessInterface
   *   The process plugin.
   */
  public function getProcessPlugin();

  /**
   * Get the test plugin.
   *
   * @return PluginTestInterface
   *   The test plugin.
   */
  public function getTestPlugin();

  /**
   * Get the deploy plugin.
   *
   * @return PluginDeployInterface
   *   The deploy plugin.
   */
  public function getDeployPlugin();

  /**
   * Get the finish plugin.
   *
   * @return PluginFinishInterface
   *   The finish plugin.
   */
  public function getFinishPlugin();

  /**
   * Set the working directory.
   *
   * Determine and prepare a temporary working directory which will be used
   * during the update process and cleaned up at the end of it. Such a temp
   * directory is useful for workflows where you checkout a working copy from
   * a repository. However, if you already have a local working copy in the
   * file system accessible to the DRD web server user, then overwrite this
   * method in your plugin and return that directory yourself.
   *
   * @return $this
   */
  public function setWorkingDirectory();

  /**
   * Prepare the working directory.
   *
   * @return $this
   */
  public function prepareWorkingDirectory();

  /**
   * Save the working directory.
   *
   * @return $this
   */
  public function saveWorkingDirectory();

}
