<?php

namespace Drupal\drd;

use Drupal\drd\Entity\MajorInterface;
use Drupal\drd\Entity\ProjectInterface;
use Drupal\drd\Entity\Release;
use Drupal\drd\Entity\ReleaseInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateProcessor as CoreUpdateProcessor;

/**
 * Process project update information.
 */
class UpdateProcessor extends CoreUpdateProcessor {

  /**
   * Mapping of stati for DRD processing.
   */
  public static function getStatuses() {
    return [
      'type' => [
        'security' => [
          'weight' => -2,
          'title' => t('Security update'),
        ],
        'unsupported' => [
          'weight' => -1,
          'title' => t('Unsupported'),
        ],
        'recommended' => [
          'weight' => 0,
          'title' => t('Recommended'),
        ],
        'ok' => [
          'weight' => 1,
          'title' => t('OK'),
        ],
      ],
      'status' => [
        UpdateManagerInterface::NOT_SECURE => [
          'type' => 'security',
          'label' => t('Not secure'),
        ],
        UpdateManagerInterface::REVOKED => [
          'type' => 'security',
          'label' => t('Revoked'),
        ],
        UpdateManagerInterface::NOT_SUPPORTED => [
          'type' => 'unsupported',
          'label' => t('Not supported'),
        ],
        UpdateManagerInterface::NOT_CURRENT => [
          'type' => 'recommended',
          'label' => t('Not current'),
        ],
        UpdateManagerInterface::CURRENT => [
          'type' => 'ok',
          'label' => t('Current'),
        ],
      ],
    ];
  }

  /**
   * Build a normalized array of project data.
   *
   * @param string $name
   *   The project name.
   * @param string $type
   *   The project type.
   * @param string $core
   *   The core version of the project.
   *
   * @return array
   *   Normalized array.
   */
  private function buildProjectData($name, $type, $core) {
    return [
      'name' => $name,
      'core' => $core . '.x',
      'includes' => [],
      'project_type' => $type,
    ];
  }

  /**
   * Calculate the project update status.
   *
   * @param \Drupal\drd\Entity\ProjectInterface $project
   *   The project entity.
   * @param \Drupal\drd\Entity\MajorInterface $major
   *   The major entity.
   * @param \Drupal\drd\Entity\ReleaseInterface $release
   *   The release entity.
   * @param array $available
   *   Available data from drupal.org.
   */
  private function calculate(ProjectInterface $project, MajorInterface $major, ReleaseInterface $release, array $available) {
    $project_data = [
      'existing_major' => $major->getMajorVersion(),
      'existing_version' => $release->getVersion(),
      'install_type' => (strpos($release->getVersion(), 'dev') !== FALSE) ? 'dev' : 'official',
    ];
    if($project_data['install_type'] == 'dev'){
      if(empty($release->information->first()->toArray()['info']['datestamp'])) {
        $project_data['status'] = UpdateFetcherInterface::NOT_CHECKED;
      }else {
        $project_data['datestamp'] = $release->information->first()
          ->toArray()['info']['datestamp'];
      }
    }
    update_calculate_project_update_status($project_data, $available);
    $release->set('updatestatus', $project_data['status']);
    $release->set('updateinfo', $project_data);
    $release->save();
    if (!empty($project_data['recommended'])) {
      $recommended = Release::findOrCreate($project->getType(), $project->getName(), $project_data['recommended']);
      if ($release->getVersion() != $recommended->getVersion()) {
        $this->calculate($project, $major, $recommended, $available);
      }
      $major->setRecommendedRelease($recommended);
    }
    if (!empty($project_data['title'])) {
      $project->setLabel($project_data['title']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchData() {
    module_load_include('inc', 'update', 'update.compare');
    foreach ([8, 7, 6] as $version) {
      $processed = [];
      $ids = \Drupal::entityQuery('drd_major')
        ->condition('coreversion', $version)
        ->condition('hidden', 0)
        ->notExists('parentproject')
        ->execute();
      if (empty($ids)) {
        continue;
      }
      /** @var \Drupal\drd\Entity\MajorInterface $major */
      foreach (\Drupal::entityTypeManager()->getStorage('drd_major')->loadMultiple($ids) as $major) {
        $project = $major->getProject();
        if (!isset($processed[$project->getName()])) {
          $p = $this->buildProjectData($project->getName(), $project->get('type'), $version);
          if ($this->processFetchTask($p)) {
            $processed[$project->getName()] = $this->availableReleasesTempStore->get($project->getName());
            if ($version == 6) {
              $this->adjustD6Data($processed[$project->getName()]);
            }
          }
          else {
            $processed[$project->getName()] = FALSE;
          }
        }
        $available = $processed[$project->getName()];
        if ($available) {
          $rids = \Drupal::entityQuery('drd_release')
            ->condition('major', $major->id())
            ->execute();
          /** @var \Drupal\drd\Entity\ReleaseInterface $release */
          foreach (\Drupal::entityTypeManager()->getStorage('drd_release')->loadMultiple($rids) as $release) {
            $this->calculate($project, $major, $release, $available);
          }
          $major->set('information', $available);
          $major
            ->updateStatus()
            ->save();
          $project->save();
        }
      }
    }

    // Delete stored information about available releases.
    \Drupal::keyValueExpirable('update_available_releases')->deleteAll();
  }

  /**
   * Adjust project data for old D6 structure.
   *
   * @param array $available
   *   The project data.
   */
  private function adjustD6Data(array &$available) {
    if (!$available || empty($available['releases'])) {
      return;
    }
    $latest_major = 0;
    foreach ($available['releases'] as $release) {
      if ($release['version_major'] > $latest_major) {
        $latest_major = $release['version_major'];
        $available['project_status'] = $release['status'];
      }
    }
    if (!$latest_major) {
      return;
    }

    $available['supported_majors'] = $latest_major;
    $available['recommended_major'] = $latest_major;
    $available['default_major'] = $latest_major;
  }

}
