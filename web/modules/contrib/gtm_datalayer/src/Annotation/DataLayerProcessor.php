<?php

namespace Drupal\gtm_datalayer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GTM dataLayer Processor annotation object.
 *
 * @Annotation
 */
class DataLayerProcessor extends Plugin {

  /**
   * The plugin ID of the GTM dataLayer Processor.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GTM dataLayer Processor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the GTM dataLayer Processor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The group in the admin UI where the GTM dataLayer Processor will be
   * listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $group = '';

  /**
   * The category in the admin UI where the GTM dataLayer Processor will be
   * listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

}
