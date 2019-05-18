<?php

namespace Drupal\drd;

use Drupal\drd\Entity\Core;
use Drupal\drd\Entity\Major;
use Drupal\drd\Entity\Project;
use Drupal\drd\Entity\Release;

/**
 * Class Cleanup.
 *
 * @package Drupal\drd
 */
class Cleanup {

  /**
   * Cleanup process for all project, major and release entities.
   */
  public function execute() {
    $config = \Drupal::config('drd.general');
    if ($config->get('cleanup.releases')) {
      $this->cleanupReleases();
    }
    if ($config->get('cleanup.majors')) {
      $this->cleanupMajors();
    }
    if ($config->get('cleanup.projects')) {
      $this->cleanupProjects();
    }
  }

  /**
   * Cleanup process for all release entities.
   */
  public function cleanupReleases() {
    $query = \Drupal::database()->select('drd_release', 'r');
    $query->leftJoin('drd_domain__releases', 'd', 'r.id=d.releases_target_id');
    $query->join('drd_major', 'm', 'r.major=m.id');
    $query
      ->fields('r', ['id'])
      ->isNull('d.entity_id')
      ->isNull('m.parentproject')
      ->condition('m.recommended', 'r.id', '<>');
    foreach ($query->execute()->fetchCol() as $id) {
      $release = Release::load($id);
      $release->delete();
    }
  }

  /**
   * Cleanup process for all major entities.
   */
  public function cleanupMajors() {
    $query = \Drupal::database()->select('drd_major', 'm');
    $query->leftJoin('drd_release', 'r', 'm.id=r.major');
    $query
      ->fields('m', ['id'])
      ->isNull('r.major')
      ->isNull('m.parentproject');
    foreach ($query->execute()->fetchCol() as $id) {
      $major = Major::load($id);
      $major->delete();
    }
  }

  /**
   * Cleanup process for all project entities.
   */
  public function cleanupProjects() {
    $query = \Drupal::database()->select('drd_project', 'p');
    $query->leftJoin('drd_major', 'm', 'p.id=m.project');
    $query
      ->fields('p', ['id'])
      ->isNull('m.project');
    foreach ($query->execute()->fetchCol() as $id) {
      $project = Project::load($id);
      $project->delete();
    }
  }

  /**
   * Reset for all project, major and release entities.
   */
  public function resetAll() {
    // Remember core versions.
    $cores = [];
    foreach (Core::loadMultiple() as $core) {
      /** @var \Drupal\drd\Entity\CoreInterface $core */
      $cores[] = [
        'core' => $core,
        'version' => $core->getDrupalRelease()->getVersion(),
      ];
    }

    // Delete all entities.
    foreach (['release', 'major', 'project'] as $item) {
      $entity_type_id = 'drd_' . $item;
      $entity_type = \Drupal::entityTypeManager()
        ->getDefinition($entity_type_id);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);

      while ($entity_ids = $storage->getQuery()
        ->sort($entity_type->getKey('id'), 'ASC')
        ->range(0, 10)
        ->execute()) {
        if ($entities = $storage->loadMultiple($entity_ids)) {
          $storage->delete($entities);
        }
      }
    }

    // Re-create Drupal core versions.
    foreach ($cores as $item) {
      /** @var \Drupal\drd\Entity\CoreInterface $core */
      $core = $item['core'];
      $release = Release::findOrCreate('core', 'drupal', $item['version']);
      $core
        ->setDrupalRelease($release)
        ->save();
    }
  }

}
