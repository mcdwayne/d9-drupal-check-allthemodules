<?php
/**
 * @file
 * Provides Drupal\spectra\SpectraPluginInterface.
 */
namespace Drupal\spectra;
/**
 * An interface for all SpectraPlugin type plugins.
 */
interface SpectraPluginInterface {
    /**
     * @return string
     *   A string description of the plugin.
     */
    public function description();
}
