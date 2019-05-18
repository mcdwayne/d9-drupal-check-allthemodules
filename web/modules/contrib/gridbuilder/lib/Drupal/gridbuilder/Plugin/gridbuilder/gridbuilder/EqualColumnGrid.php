<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\Plugin\gridbuilder\gridbuilder\EqualColumnGrid.
 */

namespace Drupal\gridbuilder\Plugin\gridbuilder\gridbuilder;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\gridbuilder\Plugin\GridBuilderInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * @Plugin(
 *  id = "equal_column_grid",
 *  derivative = "Drupal\gridbuilder\Plugin\Derivative\GridBuilder"
 * )
 */
class EqualColumnGrid extends PluginBase implements GridBuilderInterface {

  /**
   * Overrides Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    // Get definition by discovering the declarative information.
    $definition = $discovery->getDefinition($plugin_id);
    parent::__construct($configuration, $plugin_id, $discovery);
  }


  /**
   * Implements Drupal\gridbuilder\Plugin\GridBuilderInterface::getGridCss().
   */
  public function getGridCss($wrapper_selector = NULL, $col_selector_prefix = NULL, $skip_spacing = FALSE) {
    $definition = $this->getDefinition();
    $grid = $definition['grid'];
    $css = '';

    // If the wrapper selector was not provided, generate one. This is useful for
    // specific administration use cases when we scope the classes by grids.
    // @todo update legacy selector (in concert with rlayout module).
    if (empty($wrapper_selector)) {
      $wrapper_selector = '.rld-container-' . $grid->id;
    }

    // If the span selector was not provided, generate one. This is useful for
    // the front end to apply varying span widths under different names.
    if (empty($col_selector_prefix)) {
      $col_selector_prefix = '.rld-span_';
    }

    // If spacing is to be skipped, override the gutter and padding temporarily.
    if ($skip_spacing) {
      $grid->gutter_width = $grid->padding_width = 0;
    }

    // @todo: type constants are in the module.
    switch ($grid->type) {
      case 1:
        $size_suffix = '%';
        // Override to 100% whatever it was.
        $grid->width = '100';
        break;
      case 0:
        $size_suffix = 'px';
        break;
    }

    // Because we use the border-box box model, we only need to substract the
    // size of margins from the full width and divide the rest by number of
    // columns to get a value for column size.
    $colwidth = ($grid->width - (($grid->columns - 1) * $grid->gutter_width)) / $grid->columns;

    $css = $wrapper_selector . ' .rld-col {
  border: 0px solid rgba(0,0,0,0);
  float: left;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
  -moz-background-clip: padding-box !important;
  -webkit-background-clip: padding-box !important;
  background-clip: padding-box !important;
  margin-left: ' . $grid->gutter_width . $size_suffix . ';
  padding: 0 ' . $grid->padding_width  . $size_suffix . ';
}
' . $wrapper_selector . ' .rld-col' . $span_selector_prefix .'first {
  margin-left: 0;
  clear: both;
}
';
    for ($i = 1; $i <= $grid->columns; $i++) {
      $css .= $wrapper_selector . ' ' . $span_selector_prefix . $i . " {\n";
      if ($i == 1) {
        // The first column does not yet have any margins.
        $css .= '  width: ' . $colwidth * $i . $size_suffix . ";\n";
      }
      elseif ($i == $grid->columns) {
        // The full width column always spans 100%.
        $css .= "  width: " . $grid->width . $size_suffix . ";\n  margin-left: 0;\n";
      }
      else {
        // Other columns absorb all columns that they need to include and one
        // less margin before them.
        $css .= '  width: ' . (($colwidth * $i) + ($grid->gutter_width * ($i -1))) . $size_suffix . ";\n";
      }
      $css .= "}\n";
    }

    return $css;
  }

}
