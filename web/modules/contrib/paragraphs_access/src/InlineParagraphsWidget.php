<?php

namespace Drupal\paragraphs_access;

use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget as pInlineParagraphsWidget;

/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * Overrides original version of widget to apply access control restriction.
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs",
 *   label = @Translation("Paragraphs Classic"),
 *   description = @Translation("A paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsWidget extends pInlineParagraphsWidget {
  use ParagraphsWidgetRewriteTrait;

}
