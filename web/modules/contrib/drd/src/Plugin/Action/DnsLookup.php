<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\HostInterface;

/**
 * Provides a 'DnsLookup' action.
 *
 * @Action(
 *  id = "drd_action_dnslookup",
 *  label = @Translation("DNS lookup for all domains"),
 *  type = "drd_host",
 * )
 */
class DnsLookup extends BaseHost {

  /**
   * {@inheritdoc}
   */
  public function restrictAccess() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $host) {
    if (!($host instanceof HostInterface)) {
      return FALSE;
    }
    $host->updateIpAddresses();
    return TRUE;
  }

}
