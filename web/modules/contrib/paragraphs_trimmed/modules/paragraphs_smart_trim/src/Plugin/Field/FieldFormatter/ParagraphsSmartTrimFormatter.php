<?php

namespace Drupal\paragraphs_smart_trim\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs_trimmed\Plugin\Field\FieldFormatter\ParagraphsTrimmedFormatterBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Plugin implementation of the 'paragraphs_trimmed_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_smart_trim",
 *   label = @Translation("Paragraphs Smart Trim"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsSmartTrimFormatter extends ParagraphsTrimmedFormatterBase {

  /**
   * {inheritdoc}
   */
  public static function getTrimFormatterType() {
    return 'smart_trim';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'summary_handler' => 'ignore',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['summary_handler']['#type'] = 'value';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // If we have a summary field, just render it and send it out
    if ($this->getSummaryFieldValue($items)) {
      return $this->getSummaryFieldElement($items);
    }

    // Render the paragraphs output
    $elements = parent::viewElements($items, $langcode);
    $output = \Drupal::service('renderer')->render($elements);
    $element = [
      '#type' => 'processed_text',
      '#text' => $output,
      '#format' => $this->getSetting('format'),
      '#langcode' => $langcode,
    ];
    // Process text using formatter
    $output = \Drupal::service('renderer')->render($element);
    // Create a basic text field item list
    $definition = \Drupal::typedDataManager()->createListDataDefinition('field_item:text');
    $text_items = \Drupal::typedDataManager()->create(
      $definition,
      [$output],
      NULL,
      $items->getEntity()->getTypedData()
    );
    // Set the text format of our new text field item list item and process
    // using the smart trim formatter viewElements method with our settings.
    $text_items->format = $this->getSetting('format');
    return $this->formatter->setSettings($this->getSettings())->viewElements($text_items, $langcode);
  }

}
