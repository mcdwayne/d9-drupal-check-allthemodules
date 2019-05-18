<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\CoreInterface;

/**
 * Provides a 'ProjectsUpdateLog' action.
 *
 * @Action(
 *  id = "drd_action_projects_update_log",
 *  label = @Translation("Show Logs of Update Projects"),
 *  type = "drd_core",
 * )
 */
class ProjectsUpdateLog extends BaseCoreRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $core) {
    if (!($core instanceof CoreInterface)) {
      return FALSE;
    }
    $list = $core->getUpdateLogList();
    if (empty($list)) {
      print('No logs available.' . PHP_EOL);
    }
    elseif ($this->arguments['list']) {
      print_r($list);
    }
    else {
      if (empty($this->arguments['id'])) {
        $item = array_pop($list);
      }
      elseif (isset($list[$this->arguments['id']])) {
        $item = $list[$this->arguments['id']];
      }
      else {
        print('Given id does not exist.' . PHP_EOL);
      }
      if (isset($item)) {
        print($core->getUpdateLog($item['timestamp']));
      }
    }
    return TRUE;
  }

}
