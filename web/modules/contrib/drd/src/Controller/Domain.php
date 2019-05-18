<?php

namespace Drupal\drd\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\drd\Entity\DomainInterface;

/**
 * Class Domain.
 *
 * @package Drupal\drd\Controller
 */
class Domain extends ControllerBase {

  /**
   * Callback being used when returning from remote setup of new domain.
   *
   * Return from remote after initially setting the configuration to then
   * retrieve core details and all other hosted domains.
   *
   * @param \Drupal\drd\Entity\DomainInterface $domain
   *   Domain entity which just has been configured.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Destination where to go next.
   */
  public function returnFromRemote(DomainInterface $domain) {
    $core = $domain->getCore();
    $domain->set('installed', 1)->save();

    // Get info from remote.
    $domain->initCore($core);

    // Get all remote domains.
    $domain->retrieveAllDomains($core);

    return $this->redirect('entity.drd_core.canonical', ['drd_core' => $core->id()]);
  }

  /**
   * Redirect to the remote domain by opening a new session.
   *
   * @param \Drupal\drd\Entity\DomainInterface $domain
   *   Domain entity for which to start a remote user session.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Remote destination with the established session or local domain page if
   *   session couldn't be established.
   */
  public function session(DomainInterface $domain) {
    $url = $domain->getSessionUrl();
    if (!$url) {
      drupal_set_message('Can not retrieve login URL from remote domain.', 'error');
      return $this->redirect('entity.drd_domain.canonical', ['drd_domain' => $domain->id()]);
    }
    return new TrustedRedirectResponse($url);
  }

}
