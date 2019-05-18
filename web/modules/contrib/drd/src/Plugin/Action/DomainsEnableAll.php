<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\CoreInterface;

/**
 * Provides a 'DomainsEnableAll' action.
 *
 * @Action(
 *  id = "drd_action_domains_enableall",
 *  label = @Translation("Enable all domains"),
 *  type = "drd_core",
 * )
 */
class DomainsEnableAll extends BaseCoreRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $core) {
    if (empty($core) || !($core instanceof CoreInterface)) {
      return FALSE;
    }

    // Get drush and drupalconsole settings from host.
    $drush = $core->getHost()->getDrush();
    $drupalconsole = $core->getHost()->getDrupalConsole();
    if (empty($drush) && empty($drupalconsole)) {
      return FALSE;
    }
    $this->setActionArgument('drush', $drush);
    $this->setActionArgument('drupalconsole', $drupalconsole);

    // Get all disabled domains from same core.
    $domains = $core->getDomains(['installed' => 0]);
    if (empty($domains)) {
      return TRUE;
    }
    $urls = [];
    $local = [];
    foreach ($domains as $domain) {
      $url = $domain->buildUrl()->toString(TRUE)->getGeneratedUrl();
      $urls[$url] = $domain->getRemoteSetupToken(FALSE);
      $local[$url] = $domain;
    }
    $this->setActionArgument('urls', $urls);

    // Submit command to remote domain.
    $response = parent::executeAction($core);
    if ($response) {
      foreach ($response as $url) {
        /** @var \Drupal\drd\Entity\DomainInterface $domain */
        $domain = $local[$url];
        $domain->set('installed', 1);
        $domain->save();
        $domain->remoteInfo();
      }
      return TRUE;
    }
    return FALSE;
  }

}
