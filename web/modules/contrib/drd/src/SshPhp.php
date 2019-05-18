<?php

namespace Drupal\drd;

/**
 * Class SshPhp.
 *
 * @package Drupal\drd
 */
class SshPhp extends Ssh {

  /**
   * {@inheritdoc}
   */
  public function login() {
    $this->connection = @ssh2_connect($this->hostname, $this->port);
    if (!$this->connection) {
      throw new \Exception('SSH connection not possible.');
    }
    switch ($this->mode) {
      case 1:
        $success = @ssh2_auth_password(
          $this->connection,
          $this->username,
          $this->password
        );
        break;

      case 2:
        $success = @ssh2_auth_pubkey_file(
          $this->connection,
          $this->username,
          $this->pubKeyFile,
          $this->privKeyFile,
          $this->passphrase
        );
        break;

      case 3:
        if (function_exists('ssh2_auth_agent')) {
          $success = @ssh2_auth_agent(
            $this->connection,
            $this->username
          );
        }
        break;

    }
    if (empty($success)) {
      throw new \Exception('SSH authentication failed.');
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exec($command) {
    $stream = ssh2_exec($this->connection, $command);
    stream_set_blocking($stream, TRUE);
    $this->output = stream_get_contents($stream);
    $this->error = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDERR));
    if (!empty($this->error)) {
      return FALSE;
    }
    return TRUE;
  }

}
