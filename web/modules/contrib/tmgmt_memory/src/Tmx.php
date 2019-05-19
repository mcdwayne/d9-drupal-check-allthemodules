<?php

namespace Drupal\tmgmt_memory;

/**
 * Export to TMX format.
 *
 * The TMX processor follows this specification:
 * @link https://www.gala-global.org/tmx-14b
 *
 * @FormatPlugin(
 *   id = "tmx",
 *   label = @Translation("TMX")
 * )
 */
class Tmx extends \XMLWriter {

  /**
   * Adds a usage translation to the xml export.
   *
   * @param $usage_translations[]
   *   The usage translation.
   */
  protected function addTranslationUnit(array $usage_translations) {
    /** @var \Drupal\tmgmt_memory\UsageInterface $source */
    $source = reset($usage_translations)->getSource();
    $this->startElement('tu');
    $job_item = $source->getJobItem();
    if ($job_item) {
      $this->startElement('prop');
      $this->writeAttribute('type', 'job-item-uuid');
      $this->text($job_item->uuid());
      $this->endElement();
    }
    $data_item_key = $source->getDataItemKey();
    if ($data_item_key) {
      $this->startElement('prop');
      $this->writeAttribute('type', 'data-key');
      $this->text($data_item_key);
      $this->endElement();
    }
    $this->startElement('prop');
    $this->writeAttribute('type', 'segment-delta');
    $this->text($source->getSegmentDelta());
    $this->endElement();
    $this->startElement('prop');
    $this->writeAttribute('type', 'segment-id');
    $this->text(hash('sha256', $source->getData()));
    $this->endElement();

    // Add source.
    $this->startElement('tuv');
    $this->writeAttribute('xml:lang', $source->getLangcode());
    $this->writeElement('seg', htmlentities($source->getData()));
    $this->endElement();

    // Add translations.
    /** @var \Drupal\tmgmt_memory\UsageTranslationInterface $usage_translation */
    foreach ($usage_translations as $usage_translation) {
      $target = $usage_translation->getTarget();
      $this->startElement('tuv');
      $this->writeAttribute('xml:lang', $target->getLangcode());
      if ($usage_translation->getQuality()) {
        $this->startElement('prop');
        $this->writeAttribute('type', 'quality');
        $this->text($usage_translation->getQuality());
        $this->endElement();
      }
      $this->writeElement('seg', htmlentities($target->getData()));
      $this->endElement();
    }

    $this->endElement();
  }

  /**
   * {@inheritdoc}
   */
  public function export($source_language) {
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $usage_storage */
    $usage_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage');
    $usages = $usage_storage->loadMultipleBySourceLanguage($source_language);

    /** @var \Drupal\tmgmt_memory\UsageTranslationStorageInterface $usage_translation_storage */
    $usage_translation_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage_translation');

    $this->openMemory();
    $this->setIndent(TRUE);
    $this->setIndentString(' ');
    $this->startDocument('1.0', 'UTF-8');

    // Root element with schema definition.
    $this->startElement('tmx');
    $this->writeAttribute('version', '1.4');
    $this->writeAttribute('xmlns', 'http://www.lisa.org/tmx14');

    $this->startElement('header');
    $this->writeAttribute('creationtool', 'tmgmt_memory');
    $this->writeAttribute('datatype', 'PlainText');
    $this->writeAttribute('segtype', 'sentence');
    $this->writeAttribute('adminlang', \Drupal::languageManager()->getCurrentLanguage()->getId());
    $this->writeAttribute('srclang', $source_language);
    $this->endElement();

    $this->startElement('body');

    foreach ($usages as $usage) {
      $usage_translations = $usage_translation_storage->loadMultipleBySourcesAndTargets([$usage]);
      if (!empty($usage_translations)) {
        $this->addTranslationUnit($usage_translations);
      }
    }

    // End the body, file and xliff tags.
    $this->endElement();
    $this->endElement();
    $this->endElement();
    $this->endDocument();
    return $this->outputMemory();
  }

}

