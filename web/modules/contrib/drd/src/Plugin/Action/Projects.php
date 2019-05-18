<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\Project;
use Drupal\drd\Entity\Release;

/**
 * Provides a 'Projects' action.
 *
 * @Action(
 *  id = "drd_action_projects",
 *  label = @Translation("Get installed projects"),
 *  type = "drd_domain",
 * )
 */
class Projects extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if ($response) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      $core = $domain->getCore();
      $lock_hacked = \Drupal::config('drd.general')->get('lock_hacked');
      $releases = [];
      $base_themes = [];
      foreach ($response as $item) {
        $item = (array) $item;
        $info = !empty($item['info']) ? $item['info'] : [
          'hidden' => TRUE,
        ];
        if (empty($info['core'])) {
          $info['core'] = $domain->getCore()->getDrupalRelease()->getVersion();
        }
        if (empty($info['version'])) {
          $info['version'] = $info['core'] . '-0.x';
        }
        /** @var \Drupal\drd\Entity\ReleaseInterface $release */
        $release = Release::findOrCreate($item['type'], $item['name'], $info['version']);
        if ($release->isJustCreated()) {
          $major = $release->getMajor();
          $project = $major->getProject();

          $release->set('information', $item);
          if (!empty($info['name'])) {
            $project->setLabel($info['name']);
          }
          if (empty($info['project'])) {
            $info['hidden'] = TRUE;
          }
          elseif ($info['project'] != $item['name']) {
            $major->setParentProject(Project::findOrCreate($item['type'], $info['project']));
          }
          $major->setHidden(!empty($info['hidden']));
          if (!empty($info['base theme']) && !in_array($info['base theme'], $base_themes)) {
            $base_themes[] = $info['base theme'];
          }

          $release->save();
          $major->save();
          $project->save();
        }
        if (!empty($item['status'])) {
          // Use the indexed array to avoid duplicates, especially later for
          // base themes.
          $releases[$item['name']] = $release;
        }
        if (isset($item['hacked'])) {
          if (!empty($item['hacked']['status'])) {
            $core->markReleaseHacked($release);
            if ($lock_hacked) {
              $core->lockRelease($release);
            }
          }
          else {
            $core->markReleaseUnhacked($release);
          }
        }

        if ($release->getProjectType() == 'core' && (empty($core->getDrupalRelease()) || $core->getDrupalRelease()->id() != $release->id())) {
          $core->setDrupalRelease($release);
          $core->save();
        }
      }
      foreach ($base_themes as $base_theme) {
        if (!isset($releases[$base_theme])) {
          $releases[$base_theme] = Release::findOrCreate('theme', $base_theme, $this->findVersion($response, $base_theme));
        }
      }
      $domain->setReleases(array_values($releases));
      $domain->save();
      $core->save();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Find the version of a base theme.
   *
   * @param array $items
   *   List of projects found remotely.
   * @param string $name
   *   Name of the base theme.
   *
   * @return string
   *   The version string.
   */
  private function findVersion(array $items, $name) {
    foreach ($items as $item) {
      $item = (array) $item;
      if (!empty($item['name']) && $item['name'] == $name) {
        return empty($item['info']['version']) ? $item['info']['core'] . '-0.x' : $item['info']['version'];
      }
    }
    return '0.x-0.x';
  }

}
