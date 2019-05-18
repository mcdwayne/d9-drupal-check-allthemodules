<?php

namespace Drupal\paragraphs_browser_previewer\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;
use Drupal\paragraphs_browser\Plugin\Field\FieldWidget\ParagraphsBrowserWidgetTrait;
use Drupal\paragraphs_previewer\Plugin\Field\FieldWidget\ParagraphsPreviewerWidgetTrait;

/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs_browser_previewer",
 *   label = @Translation("Paragraphs Browser Previewer Classic"),
 *   description = @Translation("An paragraphs inline form widget with a Browser Previewer."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsBrowserPreviewerWidget extends InlineParagraphsWidget {

  use ParagraphsPreviewerWidgetTrait, ParagraphsBrowserWidgetTrait {
    ParagraphsBrowserWidgetTrait::defaultSettings insteadof ParagraphsPreviewerWidgetTrait;
  }

}
