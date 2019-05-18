<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

/**
 * Class for installing new sites via Aegir's Web service API.
 */
class SiteInstallHostingServiceCall extends SiteCreateHostingServiceCall {

  /**
   * The remote site task being executed by this hosting service call.
   */
  const TASK_TYPE = 'install';

  /**
   * The activity that was performed by this hosting service call's execution.
   */
  const ACTION_PERFORMED = 'Site installed';

  /**
   * {@inheritdoc}
   *
   * Creates new sites via install tasks.
   */
  protected function execute() {
    $this->sendRequestAndReceiveResponse('task', [
      // Task type.
      'type' => $this->getTaskType(),
      // New site name.
      'target' => $this->getSiteName(),
      'options' => [
        // Installation profile.
        'profile' => $this->template,
        // Client username.
        'client_name' => $this->getClient()->getDisplayName(),
        // Client e-mail address.
        'client_email' => $this->getClient()->getEmail(),
      ],
    ]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function recordSuccessLogMessage() {
    $this->logger
      ->info('Remote site %sitename: Task %task created to install site for %client from %template via %class.', [
        '%sitename' => $this->getSiteName(),
        '%task' => $this->getTaskId(),
        '%client' => $this->getClient()->getDisplayName(),
        '%template' => $this->getTemplate(),
        '%class' => $this->getClassName(),
      ]);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Report status and next steps for subscribers of new sites.
   */
  public function performActionAndLogResults() {
    parent::performActionAndLogResults();

    try {
      if ($this->getTaskId()) {
        drupal_set_message(t('Your new site %site is in the process of being created. This usually takes several minutes. When complete, you will receive an e-mail with instructions for logging into it.', [
          '%site' => $this->getSiteName(),
        ]));
      }
    }
    catch (TaskCreationFailedException $e) {
      watchdog_exception('recurly_aegir', $e);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * We don't want to inherit this from our parent's TaskCreationTrait because
   * an install request doesn't actually return the task ID; it returns the site
   * ID and the install task is created afterwards. So in this case, we want to
   * link to the site path, not the task path, which is provided by our
   * grandparent.
   */
  protected function getRemoteTargetPath() {
    return SiteHostingServiceCall::getRemoteTargetPath();
  }

}
