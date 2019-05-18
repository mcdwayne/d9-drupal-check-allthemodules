<?php

namespace Drupal\available_updates_slack;

use Drupal\available_updates_slack\Exception\UndefinedTypeException;

interface SlackNotificationServiceInterface {

    /**
     * Returns an array of the given updates of a given type. The types are defined as a Plugin.
     *
     * @param string|null $type the type of the notification. If no notification type was given, it will use a predifined one.
     * @return array If updates are available for the given type, returns an array of all updates else returns an empty array
     *
     * @throws UndefinedTypeException Throws Exception when type is not defined or supported
     */
    public function getUpdates(string $type = null);

    /**
     * Sends a notification message to a configured slack webhook.
     *
     * @param string $type the type of the notification. If no notification type was given, it will use a predifined one.
     * @param array $modules list of modules to append on the message
     * @return void
     */
    public function notify(array $modules, string $type = null);
}
