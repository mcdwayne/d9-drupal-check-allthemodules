<?php

namespace Drupal\drupal_git;

/**
 * Interface GitRepositoryInterface.
 */
interface GitRepositoryInterface {

  /**
   * Returns list of tags in repo.
   *
   * @return string[]
   *   NULL  NULL => no tags
   */
  public function getTags();

  /**
   * Gets name of current branch.
   *
   * @return string
   *   Get currnet branch name.
   *
   * @throws GitException
   */
  public function getCurrentBranchName();

  /**
   * Returns list of branches in repo.
   *
   * @return string
   *   Get all branches names.
   */
  public function getBranches();

  /**
   * Returns list of local branches in repo.
   *
   * @return string
   *   Give local branches.
   */
  public function getLocalBranches();

  /**
   * Property for initializing the repo directory.
   *
   * @param string $directory
   *   Directory name.
   * @param array $params
   *   Options parameters.
   */
  public static function init($directory, array $params = NULL);

}

/**
 * Git exception handling class.
 */
class GitException extends \Exception {

}
