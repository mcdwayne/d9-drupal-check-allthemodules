<?php

namespace Drupal\acsf_sj\Api;

use Symfony\Component\Process\Process;
use Psr\Log\LogLevel;

/**
 * Provides a Scheduled Jobs API client.
 */
class SjApiClient {

  /**
   * Current domain of this site.
   *
   * @var string
   */
  private $domain;

  /**
   * Binary to add a Scheduled Job.
   *
   * @var string
   */
  private $binary;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Constructs the ACSF SJ Client.
   *
   * SjApiClient constructor.
   */
  public function __construct() {
    $this->domain = \Drupal::request()->getHost();
    $this->logger = \Drupal::logger('acsf_sj');
    $this->binary = acsf_sj_get_sjadd_path();
  }

  /**
   * Adds a scheduled job.
   *
   * @param string $command
   *   A drush command to run.
   * @param string $reason
   *   The purpose of this job.
   * @param int $timestamp
   *   Unix timestamp when the command should be run or NULL to run ASAP.
   * @param string $domain
   *   The domain to use when calling the drush command or NULL for the class
   *   to determine.
   * @param int $timeout
   *   How long in seconds the process should be allowed to run or NULL for
   *   system default.
   * @param string $drush_executable
   *   The drush binary to use, 'drush' by default. i.e. drush9.
   * @param string $drush_options
   *   A list of drush options that will be applied to the drush command. If
   *   none are provided, "-y" will be used.
   *
   * @return bool
   *   Returns TRUE on a non-zero exit code signaling that the sjadd command
   *   succeeded.
   *
   * @throws \InvalidArgumentException
   *   If arguments are invalid.
   * @throws \RuntimeException
   *   If unable to add a job because the client is not initialized.
   */
  public function addJob($command, $reason = NULL, $timestamp = NULL, $domain = NULL, $timeout = NULL, $drush_executable = NULL, $drush_options = NULL) {
    if (empty($command) || !is_string($command)) {
      throw new \InvalidArgumentException('The command argument must be a non-empty string.');
    }

    if (is_null($timestamp)) {
      $timestamp = time();
    }
    elseif (!is_numeric($timestamp) || intval($timestamp) < 0) {
      throw new \InvalidArgumentException('The timestamp argument must be a positive integer.');
    }

    if (!is_null($domain) && (empty($domain) || !is_string($domain))) {
      throw new \InvalidArgumentException('The domain argument must be a non-empty string.');
    }
    $domain = (!empty($domain)) ? $domain : $this->domain;

    if (!is_null($timeout) && (!is_numeric($timeout) || intval($timeout) < 0)) {
      throw new \InvalidArgumentException('The timeout argument must be a positive integer.');
    }

    if (!is_null($drush_executable) && (empty($drush_executable) || !is_string($drush_executable))) {
      throw new \InvalidArgumentException('The drush_executable argument must be a non-empty string.');
    }
    $drush_executable = (!empty($drush_executable)) ? $drush_executable : 'drush';

    if (is_null($drush_options)) {
      $drush_options = '-y';
    }
    elseif (empty($drush_options) || !is_string($drush_options)) {
      throw new \InvalidArgumentException('The drush_options argument must be a non-empty string.');
    }

    /*
     * Options for sjadd should be placed before its arguments so as to not be
     * mistaken for drush options.
     *
     * Argument order:
     * TIMESTAMP DOMAIN [DRUSH_COMMAND] [DRUSH_EXECUTABLE] [DRUSH_OPTIONS...]
     */
    $cmd = '';
    if (!empty($reason)) {
      $cmd .= sprintf("--reason='%s' ", $reason);
    }
    if (!empty($timeout) && is_numeric($timeout)) {
      $cmd .= sprintf('--max-exec-time=%d ', $timeout);
    }
    $cmd .= sprintf(
      "%d '%s' '%s' '%s' '%s'",
      intval($timestamp),
      $domain, $command,
      $drush_executable,
      $drush_options
    );

    $exit_code = $this->exec($cmd);
    if ($exit_code !== 0) {
      // One retry after half of a second.
      usleep(500000);
      $exit_code = $this->exec($cmd);
    }
    return $exit_code === 0;
  }

  /**
   * Executes the Scheduled Jobs command.
   *
   * @param string $command
   *   Command delivered to SJ Jobs.
   *
   * @return int
   *   The exit code of the process
   */
  protected function exec($command) {
    $command = sprintf("%s %s", $this->binary, $command);
    $process = new Process($command);
    $process->setTimeout(10);
    try {
      $process->start();
      $message = 'Process #' . $process->getPid() . ": $command has succeeded.";
      $this->logger->log(LogLevel::NOTICE, $message);
    }
    catch (\Exception $e) {
      $message = 'Process #' . $process->getPid() . ': ' . $e->getMessage();
      $this->logger->log(LogLevel::ERROR, $message);
    }
    return $process->getExitCode();
  }

}
