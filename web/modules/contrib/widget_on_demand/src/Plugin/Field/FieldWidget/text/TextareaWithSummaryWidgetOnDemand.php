<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\text\TextareaWithSummaryWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\text;

use Drupal\text\Plugin\Field\FieldWidget\TextareaWithSummaryWidget;

/**
 * Plugin implementation of the 'text_textarea_with_summary' widget on demand.
 *
 * @FieldWidget(
 *   id = "text_textarea_with_summary_on_demand",
 *   label = @Translation("Text area with a summary - on demand"),
 *   field_types = {
 *     "text_with_summary"
 *   },
 *   weight = 100,
 * )
 */
class TextareaWithSummaryWidgetOnDemand extends TextareaWithSummaryWidget {

  use WidgetOnDemandForTextFormatTrait;

}
