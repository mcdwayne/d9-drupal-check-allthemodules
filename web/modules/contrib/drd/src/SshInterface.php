<?php

namespace Drupal\drd;

/**
 * Interface for DRD SSH Commands.
 */
interface SshInterface {

  /**
   * Login to remote host.
   *
   * @return bool
   *   TRUE if successfully logged in.
   */
  public function login();

  /**
   * Execute command in the SSH session.
   *
   * @param string $command
   *   The command to execute.
   *
   * @return bool
   *   TRUE if executed successfully.
   */
  public function exec($command);

  /**
   * Get the SSH command output.
   *
   * @return string
   *   The output.
   */
  public function getOutput();

  /**
   * Get the SSH command error output.
   *
   * @return string
   *   The error output.
   */
  public function getError();

}
