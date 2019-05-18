<?php

namespace Drupal\migrate_html_to_paragraphs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration paragraphs process plugin annotation object.
 *
 * Plugin Namespace: Plugin\migrate\html\parser.
 *
 * For a working example, see
 * \Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\Img.
 *
 * @see \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlPluginManager
 * @see \Drupal\migrate_html_to_paragraphs\Plugin\MigrateHtmlParserInterface
 * @see \Drupal\migrate\ProcessPluginBase
 * @see \Drupal\migrate_html_to_paragraphs\Annotation\MigrateHtmlParserPlugin
 * @see plugin_api
 *
 * @ingroup migration
 *
 * @Annotation
 */
class MigrateHtmlParserPlugin extends Plugin {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
