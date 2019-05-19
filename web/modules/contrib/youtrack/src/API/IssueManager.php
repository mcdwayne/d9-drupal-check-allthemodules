<?php

/**
 * @file
 */

namespace Drupal\youtrack\API;

class IssueManager {
  /**
   * Constructs a IssuesManager object.
   *
   * @param ConnectionManager $connection_manager
   */
  public function __construct(ConnectionManager $connection_manager) {
    $this->connection_manager = $connection_manager;
  }

  /**
   * Get list of accessible projects.
   */
  public function createIssue($project, $summary, $description, $commands) {
    $connection = $this->connection_manager->getConnection();

    $issue = $connection->createIssue($project, $summary, array('description' => $description));

    if (!empty($commands)) {
      $connection->executeCommand($issue->id, $commands);
    }

    return $issue;
  }
}