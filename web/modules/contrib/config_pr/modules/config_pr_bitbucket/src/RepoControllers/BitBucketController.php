<?php

namespace Drupal\config_pr_bitbucket\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;

/**
 * Class to define the BitBucket controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class BitBucketController implements RepoControllerInterface {

  /**
   * Holds the controller name.
   *
   * @var string $name.
   */
  protected $name = 'BitBucket';

  /**
   * Holds the controller Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr_bitbucket.repo_controller.bitbucket';

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  public function getOpenPrs() {
    \Drupal::messenger()->addError('BitBucket controller is not ready to be used yet!');
    \Drupal::messenger()->addError('The library can be found here https://github.com/BitbucketAPI/Client/blob/master/README.md');
  }

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer) {}

  /**
   * {@inheritdoc}
   */
  public function getRepoName() {}

  /**
   * {@inheritdoc}
   */
  public function branchExists($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getSha($branch) {}

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name) {}

  /**
   * {@inheritdoc}
   */
  public function getCommitter() {}

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function createBranch($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function authenticate() {}

  /**
   * {@inheritdoc}
   */
  public function getDefaultBranch() {}

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {}

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getRepoUser() {}

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function setAuthToken($authToken) {}

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user) {}

  /**
   * {@inheritdoc}
   */
  public function getClient() {}

}
