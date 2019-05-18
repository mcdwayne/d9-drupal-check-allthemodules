<?php

namespace Drupal\measuremail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a measuremail element annotation object.
 *
 * Plugin Namespace: Plugin\MeasuremailElements
 *
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementInterface
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementBase
 * @see \Drupal\measuremail\MeasuremailElementsInterface
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsBase
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsManager
 * @see plugin_api
 *
 * @Annotation
 */
class MeasuremailElements extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the measuremail element.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the measuremail element.
   *
   * This will be shown when adding or configuring this measuremail element.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
