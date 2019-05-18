<?php

namespace Drupal\drupal_git\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Checks access for displaying configuration translation page.
 */
class DrupalGitAccessCheck implements AccessInterface {

  /**
   * Method to test the git existence.
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

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    if (($account->hasPermission('Adminstrator drupal git') && $this->isGitRepo())) {
      return AccessResult::allowed();
    }
    else {
      $message = \Drupal::messenger();
      $message->addError(t('fatal: not a git repository (or any of the parent directories): .git'));
      return AccessResult::forbidden();
    }
  }

}
