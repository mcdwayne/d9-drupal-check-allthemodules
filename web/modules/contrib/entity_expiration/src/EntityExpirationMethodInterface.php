<?php
/**
 * @file
 * Provides Drupal\entity_expiration\EntityExpirationMethodInterface.
 */
namespace Drupal\entity_expiration;
/**
 * An interface for all EntityExpirationMethod type plugins.
 */
interface EntityExpirationMethodInterface {
    /**
     * @return string
     *   A string description of the plugin.
     */
    public function description();
}
