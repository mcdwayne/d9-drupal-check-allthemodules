<?php

namespace Drupal\config_pr\RepoControllers;

use Github\Client;

/**
 * Class to define the Github Enterprise controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GithubEnterpriseController extends GithubController {

  /**
   * Holds the controller name.
   *
   * @var string $name .
   */
  protected $name = 'Github Enterprise';

  /**
   * Holds the controller Id.
   *
   * @var string $id .
   */
  protected $id = 'config_pr.repo_controller.github_enterprise';

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }
    $repo_url = \Drupal::service('config.factory')->get('config_pr.settings')->get('repo.repo_url');
    $this->client = new Client(NULL, NULL, $repo_url);
    $this->authenticate();

    return $this->client;
  }

}
