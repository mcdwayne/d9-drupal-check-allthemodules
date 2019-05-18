<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

/**
 * Fetches the list of sites via Aegir's Web service API.
 */
class SiteListHostingServiceCall extends ListHostingServiceCall {

  /**
   * The activity that was performed by this hosting service call's execution.
   */
  const ACTION_PERFORMED = 'Sites listed';

  /**
   * {@inheritdoc}
   *
   * Returns the list of existing sites.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('site.json', []);
    return $this;
  }

  /**
   * Fetch the list of sites and return it.
   *
   * @return array
   *   The list of sites.
   */
  public static function getSiteList() {
    $site_list_fetcher = static::create(\Drupal::getContainer());
    $sites = $site_list_fetcher->performActionAndLogResults()->getResponse();
    return $sites;
  }

  /**
   * {@inheritdoc}
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote hosting system: Successfully returned list of sites via %fetcher.', [
        '%fetcher' => $this->getClassName(),
      ]);
    return $this;
  }

}
