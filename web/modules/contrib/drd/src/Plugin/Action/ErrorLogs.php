<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Provides a 'DrdActionErrorLogs' action.
 *
 * @Action(
 *  id = "drd_action_error_logs",
 *  label = @Translation("Error Logs"),
 *  type = "drd_domain",
 * )
 */
class ErrorLogs extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if ($response) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if (!empty($response['php error log'])) {
        $domain->cacheErrorLog($response['php error log']);
      }
      return TRUE;
    }
    return FALSE;
  }

}
