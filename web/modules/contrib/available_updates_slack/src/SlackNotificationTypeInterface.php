<?php

namespace Drupal\available_updates_slack;

interface SlackNotificationTypeInterface {

    /**
     * Return the Slack Notificstion Types name
     *
     * @return string The name of the notification
     */
    public function getType();
    
    /**
     * Return a subset of the given list of modules that needs updating
     * 
     * @param array $modules list of all the updates module
     * 
     * @return array filtered list of modules
     */
    public function filterUpdates(array $modules);

    /**
     * Returns an array representing a Slack message
     *
     * @param array $modules list of modules to append into the message
     * @return array Slack message
     */
    public function buildMessage(array $modules);
}