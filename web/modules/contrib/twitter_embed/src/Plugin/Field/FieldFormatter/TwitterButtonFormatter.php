<?php

namespace Drupal\twitter_embed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\twitter_embed\TwitterButtonWidget;

/**
 * Plugin implementation of the 'twitter_button_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "twitter_button_formatter",
 *   label = @Translation("Twitter button"),
 *   field_types = {
 *     "twitter_embed_field"
 *   }
 * )
 */
class TwitterButtonFormatter extends TwitterFormatterBase {

  /**
   * Constructs a TwitterButtonFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    // @todo dependency injection
    $this->twitterWidget = \Drupal::service('twitter_embed.button_widget');
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return TwitterButtonWidget::getDefaultSettings()
    + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Override settings so in case of format change, the settings
    // that differ between formatters are not overridden.
    // If a setting value is not from the same set,
    // pick the default value.
    // @todo refactoring needed to generalize:
    // - to other settings differences
    // - with getSetting(key)
    // - with setDependentConfiguration() method.
    // @todo settings that are enclosed in a fieldset (display options) are not saved.
    if (!array_key_exists($this->getSetting('type'), $this->twitterWidget->getAvailableTypes())) {
      // Type does not exist for Button widget.
      $type = '';
      $this->setSetting('type', $type);
    }
    if (!array_key_exists($this->getSetting('display_style'), $this->twitterWidget->getAvailableDisplayStyles())) {
      $displayStyle = TwitterButtonWidget::getDefaultSettings()['display_style'];
      $this->setSetting('display_style', $displayStyle);
    }
    return parent::getSettings();
  }

}
