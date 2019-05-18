<?php

namespace Drupal\drd;

use Drupal\Component\Serialization\Json;
use Drupal\drd\Entity\DomainInterface;

/**
 * Class SshCommand.
 *
 * @package Drupal\drd
 */
class SshCommand {

  /**
   * DRD domain entity.
   *
   * @var \Drupal\drd\Entity\DomainInterface
   */
  protected $domain;

  /**
   * SSH connection.
   *
   * @var SshInterface
   */
  protected $connection;

  /**
   * SSH command.
   *
   * @var string
   */
  protected $command = '';

  /**
   * Set the DRD domain entity.
   *
   * @param \Drupal\drd\Entity\DomainInterface $domain
   *   The domain entity.
   *
   * @return $this
   */
  public function setDomain(DomainInterface $domain) {
    $this->domain = $domain;
    $this->initConnection();
    return $this;
  }

  /**
   * Set the SSH command.
   *
   * @param string $command
   *   The command.
   *
   * @return $this
   */
  public function setCommand($command) {
    $this->command = $command;
    return $this;
  }

  /**
   * Get the SSH output.
   *
   * @return string
   *   The output.
   */
  public function getOutput() {
    return $this->connection->getOutput();
  }

  /**
   * Get the SSH Json output.
   *
   * @return string
   *   The Json decoded output.
   */
  public function getJsonOutput() {
    try {
      return Json::decode($this->connection->getOutput());
    }
    catch (\Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Execute the SSH command.
   *
   * @return bool
   *   TRUE, if command executed successfully.
   */
  public function execute() {
    return $this->connection->exec($this->command);
  }

  /**
   * Initialize SSH connection.
   */
  private function initConnection() {
    if (empty($this->domain->getCore()->getHost()->supportsSsh())) {
      throw new \Exception('SSH for this host is disabled.');
    }

    $settings = $this->domain->getCore()->getHost()->getSshSettings();

    if (!empty($settings['host'])) {
      $host = $settings['host'];
    }
    else {
      $host = $this->domain->getDomainName();
    }

    if (!function_exists('ssh2_connect')) {
      $this->connection = new SshPhp(
        $host,
        $settings['port'],
        $settings['auth']['mode'],
        $settings['auth']['username'],
        $settings['auth']['password'],
        $settings['auth']['file_public_key'],
        $settings['auth']['file_private_key'],
        $settings['auth']['key_secret']
      );
    }
    else {
      $this->connection = new SshLibSec(
        $host,
        $settings['port'],
        $settings['auth']['mode'],
        $settings['auth']['username'],
        $settings['auth']['password'],
        $settings['auth']['file_public_key'],
        $settings['auth']['file_private_key'],
        $settings['auth']['key_secret']
      );
    }

    if (!$this->connection->login()) {
      throw new \Exception('SSH authentication failed.');
    }
  }

}
