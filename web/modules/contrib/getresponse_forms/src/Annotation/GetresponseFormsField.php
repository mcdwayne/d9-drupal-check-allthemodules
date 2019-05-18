<?php

namespace Drupal\getresponse_forms\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GetResponse form field annotation object.
 *
 * Plugin Namespace: Plugin\GetresponseFormsField
 *
 * For a working example, see
 * \Drupal\getresponse_forms\Plugin\GetresponseFormsField\CustomField
 *
 * @see \Drupal\getresponse_forms\ConfigurableFieldInterface
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see plugin_api
 *
 * @Annotation
 */
class GetresponseFormsField extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the field.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the field.
   *
   * This will be shown when adding or configuring this field.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
