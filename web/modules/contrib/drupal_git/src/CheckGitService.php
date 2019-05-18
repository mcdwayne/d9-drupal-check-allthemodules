<?php

namespace Drupal\drupal_git;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class checkGitService.
 */
class CheckGitService {

  /**
   * Function for executing the terminal command.
   *
   * @return bool
   *   Returns boolean is git found.
   */
  public function isGitRepo() {
    $process   = new Process('git rev-parse 2> /dev/null; [ $? == 0 ] && echo 1');
    $isGitRepo = FALSE;
    try {
      $process->run();
      return ((int) $process->getOutput()) ? TRUE : FALSE;
    }
    catch (ProcessFailedException $ex) {
      \Drupal::logger("drupal_git")->error($ex->getMessage());
    }
    return $isGitRepo;
  }

}
