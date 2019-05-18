<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param Drupal\node\NodeInterface $site
   *   The site to act upon.
   */
  public static function create(ContainerInterface $container, NodeInterface $site) {
    return new static(
      $container->get('logger.factory')->get('recurly_aegir'),
      $container->get('http_client'),
      $container->get('config.factory')->get('recurly_aegir.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler'),
      $site
    );
  }

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
