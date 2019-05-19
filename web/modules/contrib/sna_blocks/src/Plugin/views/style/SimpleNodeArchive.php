<?php
/**
 * @file
 * Definition of Drupal\sna_blocks\Plugin\views\style\SimpleNodeArchive.
 */

namespace Drupal\sna_blocks\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "sna_blocks",
 *   title = @Translation("Simple Node Archive"),
 *   help = @Translation("Displays result in archive formatted, with month and year that link to achive page."),
 *   theme = "sna_blocks_view_simplenodearchive",
 *   theme_file = "sna_blocks.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class SimpleNodeArchive extends StylePluginBase {
  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    // Define options.
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Options form here.
  }
}