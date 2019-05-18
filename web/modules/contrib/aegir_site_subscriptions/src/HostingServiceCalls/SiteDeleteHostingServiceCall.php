<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

/**
 * Class for deleting new sites via Aegir's Web service API.
 */
class SiteDeleteHostingServiceCall extends SiteHostingServiceCall {
  use TaskCreationTrait;

  /**
   * The remote site task being executed by this hosting service call.
   */
  const TASK_TYPE = 'delete';

  /**
   * The activity that was performed by this hosting service call's execution.
   */
  const ACTION_PERFORMED = 'Site deleted';

  /**
   * {@inheritdoc}
   *
   * Deletes a site.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('task', [
      // Task type.
      'type' => $this->getTaskType(),
      // Site to delete.
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
      ->info('Remote site %sitename: Task %task created to delete it via %fetcher.', [
        '%sitename' => $this->getSiteName(),
        '%task' => $this->getTaskId(),
        '%fetcher' => $this->getClassName(),
      ]);
    return $this;
  }

}
