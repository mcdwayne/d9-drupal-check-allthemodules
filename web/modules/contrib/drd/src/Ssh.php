<?php

namespace Drupal\drd;

/**
 * Class Ssh.
 *
 * @package Drupal\drd
 */
abstract class Ssh implements SshInterface {

  const SSH_MODE_USERNAME_PASSWORD = 1;
  const SSH_MODE_KEY = 2;
  const SSH_MODE_AGENT = 3;

  protected $connection;

  protected $hostname;
  protected $port;
  protected $mode;
  protected $username;
  protected $password;
  protected $pubKeyFile;
  protected $privKeyFile;
  protected $passphrase;

  /**
   * The output from SSH session.
   *
   * @var string
   */
  protected $output = '';

  /**
   * The error output from SSH session.
   *
   * @var string
   */
  protected $error = '';

  /**
   * Construct a SSH object.
   *
   * @param string $hostname
   *   The host name to connect to.
   * @param int $port
   *   The SSH port.
   * @param string $mode
   *   Connection mode, SSH_MODE_USERNAME_PASSWORD|SSH_MODE_KEY|SSH_MODE_AGENT.
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   * @param string $pubKeyFile
   *   Filename for the public key.
   * @param string $privKeyFile
   *   Filename for the private key.
   * @param string $passphrase
   *   Passphrase for the private key.
   */
  public function __construct($hostname, $port, $mode, $username, $password, $pubKeyFile, $privKeyFile, $passphrase) {
    $this->hostname = $hostname;
    $this->port = $port;
    $this->mode = $mode;
    $this->username = $username;
    $this->password = $password;
    $this->pubKeyFile = $pubKeyFile;
    $this->privKeyFile = $privKeyFile;
    $this->passphrase = $passphrase;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return $this->error;
  }

}
