<?php

namespace Drupal\config_pr\RepoControllers;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\config_pr\RepoControllerInterface;
use Github\Client;
use Github\Api\GitData\References;

/**
 * Class to define the Github controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GithubController implements RepoControllerInterface {

  /**
   * Holds the controller name.
   *
   * @var string $name.
   */
  protected $name = 'Github';

  /**
   * Holds the controller Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr.repo_controller.github';

  /**
   * @var $repo_user
   *   The repo user
   */
  private $repo_user;

  /**
   * @var $name
   *   The repo repo_name
   */
  private $repo_name;

  /**
   * @var $authToken
   *   The Authentication token
   */
  private $authToken;

  /**
   * @var $client
   *    The client instance
   */
  protected $client;

  /**
   * @var $committer
   *   The committer username and email
   */
  private $committer = [];

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

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user) {
    $this->repo_user = $repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name) {
    $this->repo_name = $repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer) {
    $this->committer = $committer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoUser() {
    return $this->repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoName() {
    return $this->repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommitter() {
    return $this->committer;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthToken($authToken) {
    $this->authToken = $authToken;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->getClient()->authenticate($this->authToken, NULL, Client::AUTH_URL_TOKEN);
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }

    $this->client = new Client();
    $this->authenticate();

    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs() {
    $result = [];
    $openPullRequests = $this->getClient()
      ->api('pull_request')
      ->all($this->repo_user, $this->repo_name, array('state' => 'open'));

    foreach ($openPullRequests as $item) {
      $link = Link::fromTextAndUrl(
        'Open',
        Url::fromUri(
          $item['html_url'],
          array(
            'attributes' => array(
              'target' => '_blank'
            )
          )
        )
      );

      $result[] = [
        'number' => '#' . $item['number'],
        'title' => $item['title'],
        'link' => $link,
      ];
    }

    return $result;
  }

  /**
   * Get the default branch.
   */
  public function getDefaultBranch() {
    $repoApi = new \Drupal\config_pr\RepoControllers\GithubApi($this->getClient());
    $path = '/repos/' . rawurlencode($this->repo_user) . '/' . rawurlencode($this->repo_name);
    $response = $repoApi->get($path);

    return $response['default_branch'];
  }

  /**
   * Get the Sha of the branch.
   *
   * @param $branch
   *
   * @return mixed
   */
  public function getSha($branch) {
    if ($result = $this->findBranch($branch)) {
      return $result['object']['sha'];
    }
  }

  /**
   * List branches.
   *
   * @param References $references
   *
   * @return array
   */
  private function listBranches(\Github\Api\GitData\References $references) {
    $branches = $references->branches($this->repo_user, $this->repo_name);

    return $branches;
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
   */
  public function branchExists($branchName) {
    if ($this->findBranch($branchName)) {
      return TRUE;
    }
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
   */
  private function findBranch($branchName) {
    $references = new References($this->getClient());
    $branches = $this->listBranches($references);
    foreach ($branches as $item) {
      if ($item['ref'] == 'refs/heads/' . $branchName) {
        return $item;
      }
    }
  }

  /**
   * Creates a new branch from the default branch.
   *
   * @param $branchName
   *
   * @return array
   */
  public function createBranch($branchName) {
    $references = new References($this->getClient());
    $defaultBranch = $this->getDefaultBranch();

    if ($sha = $this->getSha($defaultBranch)) {
      $params = [
        'ref' => 'refs/heads/' . $branchName,
        'sha' => $sha,
      ];

      if ($this->branchExists($branchName)) {
        return FALSE;
      }

      $branch = $references->create($this->repo_user, $this->repo_name, $params);

      return $branch;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {
    try {
      $pullRequest = $this->getClient()
        ->api('pull_request')
        ->create($this->repo_user, $this->repo_name, array(
          'base' => $base,
          'head' => $branch,
          'title' => $title,
          'body' => $body,
          'ref' => 'refs/head/' . $branch,
          'sha' => $this->getSha($branch),
        ));
      $pullRequest['number'] = $pullRequest['number'];
      $pullRequest['url'] = $pullRequest['html_url'];

      return $pullRequest;
    } catch (\Github\Exception\ValidationFailedException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
  }

  /**
   * Get the SHA of the file
   *
   * @param $path
   *    The absolute path and file repo_name.
   */
  private function getFileSha($path) {
    try {
      // Get SHA of default branch.
      if ($sha = $this->getSha($this->getDefaultBranch())) {
        // Get file SHA.
        $result = $this
          ->getClient()
          ->api('repo')
          ->contents()
          ->show($this->getRepoUser(), $this->getRepoName(), $path, $sha);

        return $result['sha'];
      }
    } catch (\Github\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {
    // Create the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->create($this->getRepoUser(), $this->getRepoName(), $path, $content, $commitMessage, $branchName, $this->getCommitter());

      return $result;
    } catch (\Github\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {
    /* Check if the file exists. @todo Is this necessary?
    if ($client
      ->api('repo')
      ->contents()
      ->exists($this->getRepoUser(), $this->getRepoName(), $path, $reference = null)) {
    }*/

    // Update the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->update($this->getRepoUser(), $this->getRepoName(), $path, $content, $commitMessage, $this->getFileSha($path), $branchName, $this->getCommitter());

      return $result;
    } catch (\Github\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName) {
    // Delete the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->rm($this->getRepoUser(), $this->getRepoName(), $path, $commitMessage, $this->getFileSha($path), $branchName, $this->getCommitter());

      return $result;
    } catch (\Github\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

}
