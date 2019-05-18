<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\DomainInterface;

/**
 * Provides a 'Ping' action.
 *
 * @Action(
 *  id = "drd_action_ping",
 *  label = @Translation("Ping"),
 *  type = "drd_domain",
 * )
 */
class Ping extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function restrictAccess() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    if (!($domain instanceof DomainInterface)) {
      return FALSE;
    }
    $response = parent::executeAction($domain);
    $result = ($response && $response['data'] == 'pong');
    if (!empty($response['data'])) {
      $this->setOutput($response['data']);
    }
    if ($result !== $domain->getLatestPingStatus(FALSE)) {
      $domain->cachePingResult($result);
    }

    $domain->set('installed', $result);
    if (!empty($this->arguments['save'])) {
      $domain->save();
    }
    return $result;
  }

}
