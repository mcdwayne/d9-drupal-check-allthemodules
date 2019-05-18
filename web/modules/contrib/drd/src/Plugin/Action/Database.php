<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Provides a 'Database' action.
 *
 * @Action(
 *  id = "drd_action_database",
 *  label = @Translation("Download a database dump"),
 *  type = "drd_domain",
 * )
 */
class Database extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $databases = parent::executeAction($domain);
    if ($databases) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      foreach ($databases as $key => $targets) {
        foreach ($targets as $target => $def) {
          $tmpFile = file_directory_temp() . DIRECTORY_SEPARATOR . implode('-', [
            'domain',
            $domain->id(),
            basename($def['file']),
          ]);
          if ($domain->download($def['file'], $tmpFile)) {
            $databases[$key][$target]['file'] = $tmpFile;
            $this->setOutput($tmpFile);
          }
          else {
            unset($databases[$key][$target]['file']);
          }
        }
      }
      if ($this->getOutput()) {
        return $databases;
      }
    }
    return FALSE;
  }

}
