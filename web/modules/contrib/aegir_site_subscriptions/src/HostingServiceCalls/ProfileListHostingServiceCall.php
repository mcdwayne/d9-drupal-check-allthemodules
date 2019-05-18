<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

/**
 * Fetches the list of profiles via Aegir's Web service API.
 */
class ProfileListHostingServiceCall extends ListHostingServiceCall {

  /**
   * The activity that was performed by this Web service call's execution.
   */
  const ACTION_PERFORMED = 'Profiles listed';

  /**
   * {@inheritdoc}
   *
   * Returns the list of allowed profiles.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('profile.json', []);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The list of query string parameters
   */
  protected function getQueryParametersToSend() {
    return ['machine_names' => 1];
  }

  /**
   * Fetch the list of profiles and return it.
   *
   * @return array
   *   The list of profiles.
   */
  public function getProfileList() {
    return $this->performActionAndLogResults()->getResponse();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote hosting system: Successfully returned list of site installation profiles via %class.', [
        '%class' => $this->getClassName(),
      ]);
    return $this;
  }

}
