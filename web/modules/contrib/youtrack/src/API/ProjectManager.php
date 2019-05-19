<?php

/**
 * @file
 */

namespace Drupal\youtrack\API;

class ProjectManager {
  /**
   * Constructs a ProjectsManager object.
   *
   * @param ConnectionManager $connection_manager
   */
  public function __construct(ConnectionManager $connection_manager) {
    $this->connection_manager = $connection_manager;
  }

  /**
   * Get list of accessible projects.
   */
  public function getAccessibleProjects() {
    $projects = $this->connection_manager->getConnection()->getAccessibleProjects();

    $accessible_projects = array();
    foreach ($projects as $project) {
      $accessible_projects[$project->getShortName()] = $project->getName();
    }

    return $accessible_projects;
  }
}