<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

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
   * @param bool $cacheable
   *   Boolean indicating if the results are cacheable.
   *
   * @return array
   *   The list of profiles.
   */
  public static function getProfileList($cacheable) {
    $profile_lister = static::create(\Drupal::getContainer());
    $profiles = $profile_lister->performActionAndLogResults()->getResponse();
    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote hosting system: Successfully returned list of site installation profiles via %class.', [
        '%class' => $this->getClassName(),
      ]);
    return $this;
  }

}
