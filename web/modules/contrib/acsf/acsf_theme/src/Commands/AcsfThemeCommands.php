<?php

namespace Drupal\acsf_theme\Commands;

use Drush\Commands\DrushCommands;

/**
 * Provides drush commands for VCS theme notifications.
 */
class AcsfThemeCommands extends DrushCommands {

  /**
   * Sends a notification to the Factory that a theme event has occurred.
   *
   * @command acsf-theme-notify
   *
   * @bootstrap max
   *
   * @option event The type of theme event to notify the Factory about. Possible
   *   values are "create", "modify", or "delete". Defaults to "modify".
   * @option theme The system name of the theme the event relates to. Only
   *   relevant for "theme" scope notifications.
   * @option nid The node ID of the entity on the Factory the theme change is
   *   associated with. The node ID for "site" and "theme" scope notifications
   *   is automatically derived from the current site. Explicitly passing the
   *   --nid option for "site" and "theme" notifications overrides the one from
   *   the current site and should match the site node ID on the Factory. For
   *   "group" scope notifications, the nid cannot be automatically derived and
   *   is therefore required.
   *
   * @param string $scope
   *   The scope of the repository to send a notification for.
   *   Possible values are "theme", "site", "group", or "global".
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws \InvalidArgumentException
   *   If any of the arguments are invalid.
   */
  public function themeNotify($scope, array $options = [
    'event' => 'modify',
    'theme' => NULL,
    'nid' => NULL,
  ]) {
    $event = $options['event'];
    $nid = $options['nid'];
    $theme = $options['theme'];

    // Do most of the validation locally to avoid depending on the validation at
    // the endpoint.
    if (empty($scope)) {
      throw new \InvalidArgumentException(dt('The scope argument is required. Possible values are "theme", "site", "group", or "global".'));
    }
    if (!in_array($scope, ['theme', 'site', 'group', 'global'])) {
      throw new \InvalidArgumentException(dt('The scope argument must be either "theme", "site", "group", or "global".'));
    }
    if (!in_array($event, ['create', 'modify', 'delete'])) {
      throw new \InvalidArgumentException(dt('Event type not supported. Possible values are "create", "modify", or "delete".'));
    }
    if ($scope === 'theme' && empty($theme)) {
      throw new \InvalidArgumentException(dt('The --theme option must be passed for "theme" scope notifications.'));
    }
    if ($scope === 'group' && empty($nid)) {
      throw new \InvalidArgumentException(dt('The --nid option must be passed for "group" scope notifications.'));
    }

    $response = \Drupal::service('acsf.theme_notification')->sendNotification($scope, $event, $nid, $theme, NULL, FALSE);
    // AcsfMessageRest always returns a 500 error code if there was a problem
    // calling the REST API.
    if ($response['code'] === 500) {
      $this->logger()->error($response['data']['message']);
    }
    else {
      $this->logger()->success($response['data']['message']);
    }
  }

}
