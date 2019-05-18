<?php

namespace Drupal\asana;

use Asana\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class Asana.
 *
 * @package Drupal\asana
 */
class Asana implements AsanaInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The asana client.
   *
   * @var client
   */
  protected $client;

  /**
   * Constructs a new Asana object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    // Getting the personal access token.
    $personal_access_token = $this->configFactory->get('asana.settings')->get('personal_access_token');

    // Authentication.
    $this->client = Client::accessToken($personal_access_token);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllProjects() {
    // Searching all the projects.
    $projects = [];
    // Getting all the workspaces.
    $workspaces = $this->client->workspaces->findAll();
    // Iterating over all the workspaces.
    foreach ($workspaces as $workspace) {
      // Getting all the projects in a workspace.
      $workspace_projects = $this->client->projects->findByWorkspace($workspace->id);
      // Iteraring over all the projects in a workspace.
      foreach ($workspace_projects as $project) {
        $projects[$project->id] = $project->name;
      }
    }

    return $projects;
  }

}
