<?php

namespace Drupal\available_updates_slack\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for the slack notification type object
 * 
 * @see \Drupal\available_updates_slack\Manager\SlackNotificationTypePluginManager
 * 
 * @Annotation
 */
class SlackNotificationType extends Plugin {
    /**
     * The plugin ID, it is going to map to the plugin type,
     * should be machine name style.
     *
     * @var string
     */
    public $id;

    /**
     * If the plugin should be enabled
     *
     * @var bool
     */
    public $enabled;

    /**
     * The plugin label
     *
     * @var string
     */
    public $label;
    
    /**
     * The Plugin Description
     *
     * @var string
     */
    public $description;
}