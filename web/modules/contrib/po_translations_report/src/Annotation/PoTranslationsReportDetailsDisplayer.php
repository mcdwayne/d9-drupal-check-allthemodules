<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Annotation\PoTranslationsReportDetailsDisplayer.
 */

namespace Drupal\po_translations_report\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object.
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class PoTranslationsReportDetailsDisplayer extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

}
