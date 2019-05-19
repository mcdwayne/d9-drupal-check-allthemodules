<?php

namespace Drupal\coc_forms_auto_export\Plugin\WebformExporter;

use Drupal\webform\Plugin\WebformExporter\DelimitedWebformExporter;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a delimited text exporter.
 *
 * @WebformExporter(
 *   id = "coc_delimited",
 *   label = @Translation("COC Delimited text"),
 *   description = @Translation("Exports only custom fields as delimited text file."),
 * )
 */
class COCDelimitedWebformExporter extends DelimitedWebformExporter {

    /**
     * {@inheritdoc}
     */
    public function writeHeader() {
        $export_options = $this->getConfiguration();
        $elements = $this->getElementsInOrder();

        $header = [];
        // Build element columns headers.
        foreach ($elements as $element) {
            $header = array_merge($header, $this->elementManager->invokeMethod('buildExportHeader', $element, $export_options));
        }

        fputcsv($this->fileHandle, $header, $this->configuration['delimiter']);
    }

    /**
     * {@inheritdoc}
     */
    public function writeSubmission(WebformSubmissionInterface $webform_submission) {
        $export_options = $this->getConfiguration();
        $elements = $this->getElementsInOrder();

        $record = [];
        // Build record element columns.
        foreach ($elements as $column_name => $element) {
            $element['#webform_key'] = $column_name;
            $record = array_merge($record, $this->elementManager->invokeMethod('buildExportRecord', $element, $webform_submission, $export_options));
        }

        fputcsv($this->fileHandle, $record, $this->configuration['delimiter']);
    }

    /**
     * Get webform elements in specified order.
     *
     * @return array
     *   An associative array containing webform elements keyed by name.
     */
    protected function getElementsInOrder() {
        if (isset($this->elements)) {
            return $this->elements;
        }

        $export_options = $this->getConfiguration();
        $elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue('view');
        if ($export_options['excluded_columns']) {
            foreach ($export_options['excluded_columns'] as $key => $excluded_column){
                if(in_array($key, array_keys($elements))){
                    $this->elements[$key] = $elements[$key];
                }
            }
        }else{
            // Replace tokens which can be used in an element's #title.
            $this->elements = $this->tokenManager->replace($elements, $this->getWebform());
        }

        return $this->elements;
    }
}
