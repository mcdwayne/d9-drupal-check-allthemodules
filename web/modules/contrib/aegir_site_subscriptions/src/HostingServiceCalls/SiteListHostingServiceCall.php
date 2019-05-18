<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

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
  public function getSiteList() {
    return $this->performActionAndLogResults()->getResponse();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote hosting system: Successfully returned list of sites via %fetcher.', [
        '%fetcher' => $this->getClassName(),
      ]);
    return $this;
  }

}
