<?php
/**
 * @file
 * Provides Drupal\zsm\ZSMPluginInterface.
 */
namespace Drupal\zsm;
/**
 * An interface for all ZSMPlugin type plugins.
 */
interface ZSMPluginInterface {
    /**
     * Provide a description of the plugin.
     * @return string
     *   A string description of the plugin.
     */
    public function description();
}
