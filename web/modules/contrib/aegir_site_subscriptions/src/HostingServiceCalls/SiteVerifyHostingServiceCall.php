<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

/**
 * Class for verifying sites via Aegir's Web service API.
 */
class SiteVerifyHostingServiceCall extends SiteHostingServiceCall {
  use TaskCreationTrait;

  /**
   * The remote site task being executed by this hosting service call.
   */
  const TASK_TYPE = 'verify';

  /**
   * The activity that was performed by this service call's execution.
   */
  const ACTION_PERFORMED = 'Site verified';

  /**
   * {@inheritdoc}
   *
   * Verifies a site.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('task', [
      'type' => $this->getTaskType(),
      'target' => $this->getSiteName(),
    ]);

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote site %sitename: Task %task created to verify it via %fetcher.', [
        '%sitename' => $this->getSiteName(),
        '%task' => $this->getTaskId(),
        '%fetcher' => $this->getClassName(),
      ]);
    return $this;
  }

}
