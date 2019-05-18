<?php

namespace Drupal\drd;

use phpseclib\File\X509;
use phpseclib\Net\SSH2;
use phpseclib\System\SSH\Agent;

/**
 * Class SshLibSec.
 *
 * @package Drupal\drd
 */
class SshLibSec extends Ssh {

  /**
   * {@inheritdoc}
   */
  public function login() {
    $this->connection = new SSH2($this->hostname, $this->port);
    switch ($this->mode) {
      case Ssh::SSH_MODE_USERNAME_PASSWORD:
        $success = $this->connection->login($this->username, $this->password);
        break;

      case Ssh::SSH_MODE_KEY:
        if (!empty($this->privKeyFile) && file_exists($this->privKeyFile)) {
          $x509 = new X509();
          $cert = $x509->loadX509(file_get_contents($this->privKeyFile));
          if ($cert) {
            $success = $this->connection->login($this->username, $cert);
          }
        }
        break;

      case Ssh::SSH_MODE_AGENT:
        $agent = new Agent();
        if ($agent) {
          $success = $this->connection->login($this->username, $agent);
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
    /** @var \phpseclib\Net\SSH2 $connection */
    $connection = $this->connection;
    $this->output = $connection->exec($command);
    if (empty($this->output)) {
      $this->error = implode(PHP_EOL, $connection->getErrors());
      return FALSE;
    }
    return TRUE;
  }

}
