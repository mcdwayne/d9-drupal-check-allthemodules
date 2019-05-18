<?php

namespace Drupal\paragraphs_access;

use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget as pParagraphsWidget;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * Overrides original version of widget to apply access control restriction.
 *
 * @FieldWidget(
 *   id = "paragraphs",
 *   label = @Translation("Paragraphs EXPERIMENTAL"),
 *   description = @Translation("An experimental paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWidget extends pParagraphsWidget {
  use ParagraphsWidgetRewriteTrait;

}
