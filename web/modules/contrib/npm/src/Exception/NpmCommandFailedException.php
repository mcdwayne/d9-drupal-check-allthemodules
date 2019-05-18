<?php

namespace Drupal\npm\Exception;

use Symfony\Component\Process\Process;

class NpmCommandFailedException extends NpmException {

  /**
   * @var \Symfony\Component\Process\Process
   */
  protected $process;

  /**
   * NpmCommandFailedException constructor.
   *
   * @param \Symfony\Component\Process\Process $process
   *   The process that failed.
   */
  public function __construct(Process $process) {
    parent::__construct($process->getOutput() . "\n\n" . $process->getErrorOutput(), $process->getExitCode());
    $this->process = $process;
  }

  /**
   * Returns the process object.
   *
   * @return \Symfony\Component\Process\Process
   */
  public function getProcess() {
    return $this->process;
  }

}
