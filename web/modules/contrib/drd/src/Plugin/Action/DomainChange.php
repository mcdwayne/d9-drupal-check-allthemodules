<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\Domain;

/**
 * Provides a 'DomainChange' action.
 *
 * @Action(
 *  id = "drd_action_domainchange",
 *  label = @Translation("Change domain, protocol and port of a domain"),
 *  type = "drd_domain",
 * )
 */
class DomainChange extends BaseEntity {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    /** @var \Drupal\drd\Entity\DomainInterface $domain */
    if (isset($this->arguments['secure'])) {
      $domain->set('secure', $this->arguments['secure']);
    }
    if (isset($this->arguments['port'])) {
      $domain->set('port', $this->arguments['port']);
    }
    if (isset($this->arguments['newdomain'])) {
      $domainname = trim($this->arguments['newdomain'], '/');
      $domain->set('domain', $domainname);
      $existing = Domain::instanceFromUrl($domain->getCore(), $domain->buildUrl()->toString(TRUE)->getGeneratedUrl(), []);
      if (!$existing->isNew()) {
        $this->setOutput('Another domain already exists.');
        return FALSE;
      }
    }

    $url = $domain->buildUrl()->toString(TRUE)->getGeneratedUrl();
    if ($this->arguments['force'] || $domain->ping()) {
      $domain->save();
      $this->setOutput('Domain changed to ' . $url);
      return TRUE;
    }

    $this->setOutput('Domain does not respond: ' . $url);
    return FALSE;
  }

}
