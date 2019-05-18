<?php

namespace Drupal\paragraphs_browser_previewer\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\paragraphs_browser\Plugin\Field\FieldWidget\ParagraphsBrowserWidgetTrait;
use Drupal\paragraphs_previewer\Plugin\Field\FieldWidget\ParagraphsPreviewerWidgetTrait;

/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "paragraphs_browser_previewer",
 *   label = @Translation("Paragraphs Browser Previewer EXPERIMENTAL"),
 *   description = @Translation("An paragraphs inline form widget with a Browser Previewer."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsBrowserPreviewerWidget extends ParagraphsWidget {

  use ParagraphsPreviewerWidgetTrait, ParagraphsBrowserWidgetTrait {
    ParagraphsBrowserWidgetTrait::defaultSettings insteadof ParagraphsPreviewerWidgetTrait;
  }

  /**
   * Returns select options for a plugin setting.
   *
   * This is done to allow
   * \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget::settingsSummary()
   * to access option labels. Not all plugin setting are available.
   *
   * @param string $setting_name
   *   The name of the widget setting. Supported settings:
   *   - "edit_mode"
   *   - "closed_mode"
   *   - "autocollapse"
   *   - "add_mode",
   *
   * @return array|null
   *   An array of setting option usable as a value for a "#options" key.
   *
   * @see \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget::settingsSummary()
   */
  protected function getSettingOptions($setting_name) {
    $options = parent::getSettingOptions($setting_name);
    switch ($setting_name) {
      case 'add_mode':
        $options['paragraphs_browser'] = $this->t('Paragraphs Browser');
        break;
    }

    return $options;
  }

}
