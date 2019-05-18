<?php

namespace Drupal\external_data_source\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for External Data Source plugins.
 */
interface ExternalDataSourceInterface extends PluginInspectionInterface {

    public function getResponse();
}
