<?php

/**
 * @file
 * Contains Drupal\po_translations_report\PoReporter.
 */

namespace Drupal\po_translations_report;

use Drupal\Component\Gettext\PoStreamReader;

/**
 * PoReporter class.
 */
class PoReporter {

  /**
   * Complete file path.
   *
   * @var string
   */
  protected $filePath = '';

  /**
   * Count of translated strings per file.
   *
   * @var int
   */
  protected $translatedCount = 0;

  /**
   * Count of untranslated strings per file.
   *
   * @var int
   */
  protected $untranslatedCount = 0;

  /**
   * Count of strings that contain non allowed HTML tags for translation.
   *
   * @var int
   */
  protected $notAllowedTranslationCount = 0;

  /**
   * Count of strings per file.
   *
   * @var int
   */
  protected $totalCount = 0;

  /**
   * PoReporter method.
   *
   * @param string $filepath
   *   The file path.
   *
   * @return array
   *   Array of data.
   *
   * @throws \Exception
   *  Exception.
   */
  public function poReport($filepath) {
    $this->initializeProperties();
    $this->setfilePath($filepath);
    // Instantiate and initialize the stream reader for this file.
    $reader = new PoStreamReader();

    $reader->setURI($filepath);
    $reader->open();
    $header = $reader->getHeader();
    if (!$header) {
      throw new \Exception('Missing or malformed header.');
    }
    while ($item = $reader->readItem()) {
      if (!$item->isPlural()) {
        $this->translationReport($item->getTranslation());
      }
      else {
        // Plural case.
        $plural = $item->getTranslation();
        foreach ($item->getSource() as $key => $source) {
          $this->translationReport($plural[$key]);
        }
      }
    }

    return array(
      'file_name' => basename($filepath),
      'translated' => $this->getTranslatedCount(),
      'untranslated' => $this->getUntranslatedCount(),
      'not_allowed_translations' => $this->getNotAllowedTranslatedCount(),
      'total_per_file' => $this->getTotalCount(),
    );
  }

  /**
   * Update translation report counts.
   *
   * @param string $translation
   *   Contains the translated string.
   */
  public function translationReport($translation) {

    if (locale_string_is_safe($translation)) {
      if ($translation != '') {
        $this->SetTranslatedCount(1);
      }
      else {
        $this->SetUntranslatedCount(1);
      }
    }
    else {
      $this->SetNotAllowedTranslatedCount(1);
    }
    $this->SetTotalCount(1);
  }

  /**
   * Getter for filePath.
   *
   * @return string
   *   file path.
   */
  public function getfilePath() {
    return $this->filePath;
  }

  /**
   * Getter for translatedCount.
   *
   * @return int
   *   Translated count.
   */
  public function getTranslatedCount() {
    return $this->translatedCount;
  }

  /**
   * Getter for untranslatedCount.
   *
   * @return int
   *   Untranslated count.
   */
  public function getUntranslatedCount() {
    return $this->untranslatedCount;
  }

  /**
   * Getter for notAllowedTranslatedCount.
   *
   * @return int
   *   Not allowed translation count.
   */
  public function getNotAllowedTranslatedCount() {
    return $this->notAllowedTranslationCount;
  }

  /**
   * Getter for totalCount.
   *
   * @return int
   *   Total count.
   */
  public function getTotalCount() {
    return $this->totalCount;
  }

  /**
   * Setter for filePath.
   *
   * @param string $path
   *   The file path.
   */
  public function setfilePath($path) {
    $this->filePath = $path;
  }

  /**
   * Setter for translatedCount.
   *
   * @param int $count
   *   The value to add to translated count.
   */
  public function setTranslatedCount($count) {
    $this->translatedCount += $count;
  }

  /**
   * Setter for untranslatedCount.
   *
   * @param int $count
   *   The value to add to untranslated count.
   */
  public function setUntranslatedCount($count) {
    $this->untranslatedCount += $count;
  }

  /**
   * Setter for notAllowedTranslatedCount.
   *
   * @param int $count
   *   The value to add to not allowed translated count.
   */
  public function setNotAllowedTranslatedCount($count) {
    $this->notAllowedTranslationCount += $count;
  }

  /**
   * Setter for totalCount.
   *
   * @param int $count
   *   The value to add to the total count.
   */
  public function setTotalCount($count) {
    $this->totalCount += $count;
  }

  /**
   * Initializes properties.
   */
  public function initializeProperties() {
    $this->filePath = '';
    $this->translatedCount = 0;
    $this->untranslatedCount = 0;
    $this->notAllowedTranslationCount = 0;
    $this->totalCount = 0;
  }

}
