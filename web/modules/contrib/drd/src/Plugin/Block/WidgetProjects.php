<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\update\UpdateManagerInterface;

/**
 * Provides a 'WidgetProjects' block.
 *
 * @Block(
 *  id = "drd_projects",
 *  admin_label = @Translation("DRD Projects"),
 *  weight = -4,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetProjects extends WidgetEntities {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Projects');
  }

  /**
   * {@inheritdoc}
   */
  protected function type() {
    return 'project';
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    $query = \Drupal::database()->select('drd_project', 'p');
    $query->join('drd_major', 'm', 'm.project = p.id');
    $query
      ->condition('m.hidden', 0)
      ->isNull('m.parentproject');
    $projects = $query
      ->countQuery()
      ->execute()
      ->fetchField();
    $query = \Drupal::database()->select('drd_release', 'r');
    $query->join('drd_major', 'm', 'm.id = r.major');
    $query
      ->condition('m.hidden', 0)
      ->isNull('m.parentproject');
    $releases = $query
      ->countQuery()
      ->execute()
      ->fetchField();
    $query = \Drupal::database()->select('drd_release', 'r');
    $query->join('drd_major', 'm', 'm.id = r.major');
    $query
      ->condition('r.updatestatus', [
        UpdateManagerInterface::NOT_SECURE,
        UpdateManagerInterface::NOT_SUPPORTED,
        UpdateManagerInterface::REVOKED,
      ], 'IN')
      ->condition('m.hidden', 0)
      ->isNull('m.parentproject');
    $critical = $query
      ->countQuery()
      ->execute()
      ->fetchField();
    $query = \Drupal::database()->select('drd_release', 'r');
    $query->join('drd_major', 'm', 'm.id = r.major');
    $query
      ->condition('r.updatestatus', [
        UpdateManagerInterface::NOT_CURRENT,
      ], 'IN')
      ->condition('m.hidden', 0)
      ->isNull('m.parentproject');
    $recommended = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($projects == 0) {
      $args = [];
      $message = '<p>You currently have no projects in DRD!</p>';
    }
    else {
      $args = [
        '%count1' => $projects,
        '%count2' => $releases,
      ];
      $message = '<p class="message">You have %count1 projects and %count2 releases.</p>';
      if ($this->accessView) {
        $args['@projectlist'] = (new Url('entity.drd_project.collection'))->toString();
        $message .= '<p>Get all the details in your <a href="@projectlist">projects list</a>.';
      }
    }
    if ($this->accessView) {
      $args['@domainlist'] = (new Url('entity.drd_domain.collection'))->toString();
      $message .= '<p>Projects will automatically be recognised from your domains when you execute the <strong>Collect used projects</strong> action in the <a href="@domainlist">domain list</a>.</p>';
    }

    if ($critical > 0) {
      $args['%count3'] = $critical;
      $message .= '<p class="message critical">You have %count3 critical updates available.</p>';
    }
    if ($recommended > 0) {
      $args['%count4'] = $recommended;
      $message .= '<p class="message recommended">There are %count4 other updates available.</p>';
    }

    return new FormattableMarkup($message, $args);
  }

}
