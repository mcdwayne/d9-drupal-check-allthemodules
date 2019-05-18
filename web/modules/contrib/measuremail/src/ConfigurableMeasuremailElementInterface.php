<?php

namespace Drupal\measuremail;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable measuremail elements.
 *
 * @see \Drupal\measuremail\Annotation\MeasuremailElements
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementBase
 * @see \Drupal\measuremail\MeasuremailElementsInterface
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsBase
 * @see \Drupal\measuremail\Plugin\MeasuremailElementManager
 * @see plugin_api
 */
interface ConfigurableMeasuremailElementInterface extends MeasuremailElementsInterface, PluginFormInterface {
}
