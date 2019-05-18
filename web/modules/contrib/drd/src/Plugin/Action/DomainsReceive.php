<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\Domain;
use GuzzleHttp\Client;

/**
 * Provides a 'DomainsReceive' action.
 *
 * @Action(
 *  id = "drd_action_domains_receive",
 *  label = @Translation("Receive domains"),
 *  type = "drd_core",
 * )
 */
class DomainsReceive extends BaseCoreRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $core) {
    /* @var \Drupal\drd\Entity\CoreInterface $core */
    $response = parent::executeAction($core);
    if (!$response) {
      return FALSE;
    }

    $known_domains = $core->getDomains();

    /* @var \Drupal\drd\Entity\DomainInterface $domain */
    $domain = $this->drdEntity;
    $crypt = $domain->getCrypt();
    $crypt_setting = $domain->getCryptSetting();

    $newDomains = 0;
    foreach ($response as $shortname => $item) {
      // Find out scheme.
      $url = 'https://' . $item['uri'];
      $status_code = 0;
      try {
        $client = new Client();
        $response = $client->head($url, ['allow_redirects' => FALSE]);
        $status_code = $response->getStatusCode();
      }
      catch (\Exception $ex) {
      }
      if ($status_code >= 200 && $status_code < 300) {
        // This seems OK.
      }
      else {
        // Lets use http instead.
        $url = 'http://' . $item['uri'];
      }

      $d = Domain::instanceFromUrl($core, $url, []);
      if ($d->isNew()) {
        if ($d->ping()) {
          $d->remoteInfo();
        }
        else {
          // Leave name of domain empty such that we can see that it is not
          // enabled yet.
          $d->initValues('', $crypt, $crypt_setting);
          $newDomains++;
        }
      }
      else {
        // Remove this domain from the list of $known_domains.
        foreach ($known_domains as $key => $known_domain) {
          if ($known_domain->id() == $d->id()) {
            unset($known_domains[$key]);
            break;
          }
        }
      }
      $d
        ->setAliase($item['aliase'])
        ->updateScheme($url)
        ->save();
    }

    // Delete domains that no longer exist.
    foreach ($known_domains as $known_domain) {
      if ($known_domain->id() == $domain->id()) {
        // Do not delete the current domain, it's probably the first one which
        // was used to create the host but isn't included in the remote
        // sites.php, @see https://www.drupal.org/node/2840203
        continue;
      }
      $known_domain->delete();
    }

    // Make sure all new domains are enabled as well.
    if ($newDomains) {
      BaseEntityRemote::response('drd_action_domains_enableall', $core);
    }

    return TRUE;
  }

}
