<?php

namespace Drupal\redmine_connector;

use Drupal\node\Entity\Node;

/**
 * Class SynchronizationService.
 */
class SynchronizationService {

  /**
   * Function for projects synchronization.
   */
  public function synchronizeProjects() {
    // Getting PROJECTS from Redmine.
    $data = \Drupal::service('redmine_connector.connect')->getData('projects');
    if ($data === FALSE) {
      drupal_set_message(t('Please fill out the <a href="@link">Redmine Connector Settings</a> form.', array('@link' => '/admin/config/redmine_connector/settings')));
    }

    // Creating or updating nodes due to Redmine data.
    foreach ($data['projects'] as $project) {
      // Getting spent hours for different periods.
      $spent_hours = \Drupal::service('redmine_connector.grouping')
        ->getProjectTimeEntriesForDifferentPeriods($project['id']);

      // Checking if node is already created.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'project')
        ->condition('rm_pid', $project['id']);
      $nid = $query->execute();
      !empty($nid) ? $already_created = TRUE : $already_created = FALSE;

      // Creating or updating nodes depending on the $already_created parameter.
      if ($already_created === FALSE) {
        $node = Node::create([
          'type' => 'project',
          'title' => $project['name'],
          'rm_pid' => $project['id'],
          'redmine_project_description' => $project['description'],
          'spent_hours' => $spent_hours['spent_hours_all'],
          'rm_ptime_lm' => $spent_hours['spent_hours_lm'],
          'rm_ptime_lw' => $spent_hours['spent_hours_lw'],
          'rm_ptime_tm' => $spent_hours['spent_hours_tm'],
          'rm_ptime_tw' => $spent_hours['spent_hours_tw'],
        ]);
        $node->save();
      }
      else {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'project')
          ->condition('rm_pid', $project['id']);
        $nid_array = $query->execute();
        $nid = array_shift($nid_array);
        $node = Node::load($nid);
        if ($node->getTitle() != $project['name']) {
          $node->set('title', $project['name']);
        }
        if ($node->get('redmine_project_description')->value != $project['description']) {
          $node->set('redmine_project_description', $project['description']);
        }
        if ($node->get('spent_hours')->value != $spent_hours['spent_hours_all']) {
          $node->set('spent_hours', $spent_hours['spent_hours_all']);
        }
        if ($node->get('rm_ptime_lm')->value != $spent_hours['spent_hours_lm']) {
          $node->set('rm_ptime_lm', $spent_hours['spent_hours_lm']);
        }
        if ($node->get('rm_ptime_lw')->value != $spent_hours['spent_hours_lw']) {
          $node->set('rm_ptime_lw', $spent_hours['spent_hours_lw']);
        }
        if ($node->get('rm_ptime_tm')->value != $spent_hours['spent_hours_tm']) {
          $node->set('rm_ptime_tm', $spent_hours['spent_hours_tm']);
        }
        if ($node->get('rm_ptime_tw')->value != $spent_hours['spent_hours_tw']) {
          $node->set('rm_ptime_tw', $spent_hours['spent_hours_tw']);
        }
        $node->save();
      }
    }
  }

  /**
   * Function for users synchronization.
   */
  public function synchronizeUsers() {
    // Getting USERS from Redmine.
    $employee_data = \Drupal::service('redmine_connector.connect')
      ->getData('users');

    // Creating or updating nodes due to Redmine data.
    foreach ($employee_data['users'] as $user) {
      // Getting spent hours for different periods.
      $spent_hours = \Drupal::service('redmine_connector.grouping')
        ->getUserTimeEntriesForDifferentPeriods($user['id']);

      // Checking if node is already created.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'redmine_user')
        ->condition('rm_uid', $user['id']);
      $nid = $query->execute();
      !empty($nid) ? $already_created = TRUE : $already_created = FALSE;

      // Creating or updating nodes depending on the $already_created parameter.
      if ($already_created === FALSE) {
        $node = Node::create([
          'type' => 'redmine_user',
          'title' => $user['firstname'] . " " . $user['lastname'],
          'rm_uid' => $user['id'],
          'redmine_user_email' => $user['mail'],
          'spent_hours' => $spent_hours['spent_hours_all'],
          'rm_utime_lm' => $spent_hours['spent_hours_lm'],
          'rm_utime_lw' => $spent_hours['spent_hours_lw'],
          'rm_utime_tm' => $spent_hours['spent_hours_tm'],
          'rm_utime_tw' => $spent_hours['spent_hours_tw'],
        ]);
        $node->save();
      }
      else {
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'redmine_user')
          ->condition('rm_uid', $user['id']);
        $nid_array = $query->execute();
        $nid = array_shift($nid_array);
        $node = Node::load($nid);
        if ($node->getTitle() != $user['firstname'] . " " . $user['lastname']) {
          $node->set('title', $user['firstname'] . " " . $user['lastname']);
        }
        if ($node->get('redmine_user_email')->value != $user['mail']) {
          $node->set('redmine_user_email', $user['mail']);
        }
        if ($node->get('spent_hours')->value != $spent_hours['spent_hours_all']) {
          $node->set('spent_hours', $spent_hours['spent_hours_all']);
        }
        if ($node->get('rm_utime_lm')->value != $spent_hours['spent_hours_lm']) {
          $node->set('rm_utime_lm', $spent_hours['spent_hours_lm']);
        }
        if ($node->get('rm_utime_lw')->value != $spent_hours['spent_hours_lw']) {
          $node->set('rm_utime_lw', $spent_hours['spent_hours_lw']);
        }
        if ($node->get('rm_utime_tm')->value != $spent_hours['spent_hours_tm']) {
          $node->set('rm_utime_tm', $spent_hours['spent_hours_tm']);
        }
        if ($node->get('rm_utime_tw')->value != $spent_hours['spent_hours_tw']) {
          $node->set('rm_utime_tw', $spent_hours['spent_hours_tw']);
        }
        $node->save();
      }
    }
  }

  /**
   * Function for issues synchronization.
   */
  public function synchronizeIssues() {
    // Getting USERS from Redmine.
    $employee_data = \Drupal::service('redmine_connector.connect')
      ->getData('users');
    // Getting ISSUES from Redmine.
    // Deleting all nodes of type Issue from site
    // before loading new data from Redmine.
    $result = \Drupal::entityQuery('node')
      ->condition('type', 'issue')
      ->execute();
    foreach (Node::loadMultiple($result) as $node) {
      $node->delete();
    }

    // Getting data and creating new nodes.
    foreach ($employee_data['users'] as $user) {
      // Getting assigned issues for all users.
      $user_assigned_issues = \Drupal::service('redmine_connector.connect')
        ->getData('issues', ['assigned_to_id' => $user['id']]);

      // Creating or updating nodes due to Redmine data.
      foreach ($user_assigned_issues['issues'] as $issue) {
        // Loading user assigned to this issue and getting target id.
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'redmine_user')
          ->condition('rm_uid', $issue['assigned_to']['id']);
        $nid_array = $query->execute();
        $nid = array_shift($nid_array);
        $assigned_to_node = Node::load($nid);
        $node = Node::create([
          'type' => 'issue',
          'title' => $issue['subject'],
          'rm_iid' => $issue['id'],
          'redmine_issue_assigned_to' => [
            ['target_id' => $assigned_to_node->get('nid')->value],
          ],
          'redmine_issue_author' => $issue['author']['name'],
          'redmine_issue_created_time' => [
            date(DATETIME_DATETIME_STORAGE_FORMAT, strtotime($issue['created_on'])),
          ],
          'redmine_issue_description' => $issue['description'],
          'redmine_issue_estimated_hours' => $issue['estimated_hours'],
          'redmine_issue_priority' => $issue['priority']['name'],
          'redmine_issue_project' => $issue['project']['name'],
          'redmine_issue_updated_time' => [
            date(DATETIME_DATETIME_STORAGE_FORMAT, strtotime($issue['updated_on'])),
          ],
        ]);
        $node->save();
      }
    }
  }

}
