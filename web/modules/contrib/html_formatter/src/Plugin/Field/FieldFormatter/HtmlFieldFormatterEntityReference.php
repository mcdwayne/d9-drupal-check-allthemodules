<?php

namespace Drupal\html_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\html_formatter\Plugin\HtmlFormatterTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'entity reference label' html field formatter.
 *
 * @FieldFormatter(
 *   id = "html_field_formatter_entity_reference_label",
 *   label = @Translation("HTML Field Formatter Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class HtmlFieldFormatterEntityReference extends EntityReferenceLabelFormatter {

  use HtmlFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return HtmlFormatterTrait::getHtmlFormatterDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form += $this->getHtmlFormatterSettingsForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary = array_merge($summary, $this->getHtmlFormatterSettingsSummary($this->settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $delta => $element) {
      if (isset($element['#plaintext'])) {
        $value = $element['#plaintext'];
      }
      else {
        $value = $elements[$delta];
      }

      $elements[$delta]['#theme'] = 'html_formatter';
      $elements[$delta]['#value'] = $value;
      $elements[$delta]['#tag'] = $this->getSetting('tag');
      $elements[$delta]['#attributes']['class'] = $this->getSetting('class');
    }

    return $elements;
  }

}
