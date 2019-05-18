<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\CoreInterface;

/**
 * Provides a 'ProjectsUpdate' action.
 *
 * @Action(
 *  id = "drd_action_projects_update",
 *  label = @Translation("Update Projects"),
 *  type = "drd_core",
 * )
 */
class ProjectsUpdate extends BaseCoreRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $core) {
    $this->reset();
    if (!($core instanceof CoreInterface)) {
      return FALSE;
    }

    try {
      $plugin = $core->getUpdatePlugin();
    }
    catch (\Exception $ex) {
      $this->setOutput($ex->getMessage());
      return TRUE;
    }

    $includeLocked = !empty($this->arguments['include-locked']);
    $securityOnly = !empty($this->arguments['security-only']);
    $forceLockedSecurity = !empty($this->arguments['force-locked-security']);
    $releases = $core->getAvailableUpdates($includeLocked, $securityOnly, $forceLockedSecurity);
    if (!empty($releases)) {
      if (!empty($this->arguments['list'])) {
        foreach ($releases as $release) {
          $this->drdEntity = $release;
          $this->log('info', '@project', [
            '@project' => $release->getMajor()->getProject()->getLabel(),
          ]);
        }
        return TRUE;
      }

      $dry = !empty($this->arguments['dry-run']);
      $showlog = !empty($this->arguments['show-log']);
      $result = $plugin->execute($core, $releases, $dry, $showlog);
      if ($result !== TRUE) {
        $this->setOutput($result);
        return FALSE;
      }
    }

    return TRUE;
  }

}
