<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Provides a 'Session' action.
 *
 * @Action(
 *  id = "drd_action_session",
 *  label = @Translation("Get a session URL"),
 *  type = "drd_domain",
 * )
 */
class Session extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if (!empty($response['url'])) {
      $this->setOutput($response['url']);
      return $response['url'];
    }
    return FALSE;
  }

}
