<?php

namespace Drupal\paragraphs_trimmed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\paragraphs_trimmed\Plugin\Field\FieldFormatter\ParagraphsTrimmedFormatterBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Plugin implementation of the 'paragraphs_trimmed' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_trimmed",
 *   label = @Translation("Paragraphs Trimmed"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsTrimmedFormatter extends ParagraphsTrimmedFormatterBase {

  /**
   * {inheritdoc}
   */
  public static function getTrimFormatterType() {
    return 'text_trimmed';
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
