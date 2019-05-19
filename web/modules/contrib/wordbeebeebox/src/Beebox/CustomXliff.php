<?php

namespace Drupal\tmgmt_wordbee\Beebox;
use Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Xliff;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Entity\Job;

class CustomXliff extends Xliff {

    /**
     * Same as Xliff::addTransUnit() exept it leaves the target element empty
     *
     * @param string $key
     * @param array $element
     */
    protected function addTransUnit($key, $element, JobInterface $job) {
        $key_array = \Drupal::service('tmgmt.data')->ensureArrayKey($key);

        $this->startElement('trans-unit');
        $this->writeAttribute('id', $key);
        $this->writeAttribute('resname', $key);

        $this->startElement('source');
        $this->writeAttribute('xml:lang', $this->job->getTranslator()->mapToRemoteLanguage($this->job->source_language));

        if ($job->getSetting('xliff_processing')) {
            $this->writeRaw($this->processForExport($element['#text'], $key_array));
        }
        else {
            $this->text($element['#text']);
        }

        $this->endElement();
        $this->startElement('target');
        $this->endElement();
        if (isset($element['#label'])) {
            $this->writeElement('note', $element['#label']);
        }
        $this->endElement();
  }

  /**
   * Same as Xliff::import() exept it takes a XML string as parameter
   *
   * @param string $xml_string
   * @return array
   */
  public function import($xml_string, $is_file = true) {
    $xml = simplexml_load_string($xml_string);

    // Register the xliff namespace, required for xpath.
    $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

    $data = array();
    foreach ($xml->xpath('//xliff:trans-unit') as $unit) {
      $data[(string) $unit['id']]['#text'] = (string) $unit->target;
    }
    return \Drupal::service('tmgmt.data')->unflatten($data);
  }

  /**
   * Same as Xliff::validateImport() exept it takes a XML string as parameter
   *
   * @param string $xml_string
   * @return boolean
   */
  public function validateImport($xml_string, $is_file = true) {
    $xml = simplexml_load_string($xml_string);

    if (!$xml) {
      return FALSE;
    }

    // Register the xliff namespace, required for xpath.
    $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

    // Check if our phase information is there.
    $phase = $xml->xpath("//xliff:phase[@phase-name='extraction']");
    if ($phase) {
      $phase = reset($phase);
    }
    else {
      return FALSE;
    }

    // Check if the job can be loaded.
    if (!isset($phase['job-id']) || (!$job = Job::load((string) $phase['job-id']))) {
      return FALSE;
    }

    // Compare source language.
    if (!isset($xml->file['source-language']) || $job->getRemoteSourceLanguage() != $xml->file['source-language']) {
      return FALSE;
    }

    // Compare target language.
    if (!isset($xml->file['target-language']) || $job->getRemoteTargetLanguage() != $xml->file['target-language']) {
      return FALSE;
    }

    // Validation successful.
    return $job;
  }
}
