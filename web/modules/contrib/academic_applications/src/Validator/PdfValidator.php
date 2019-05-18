<?php

namespace Drupal\academic_applications\Validator;

use Drupal\file\Entity\File;
use Drupal\Core\Form\FormStateInterface;

/**
 * Validation utilities for form elements and files.
 */
class PdfValidator {

  /**
   * Validates a file form element that handles PDFs.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface &$form_state) {
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button != 'remove_button' && !empty($element['fids']['#value'])) {
      foreach ($element['#files'] as $file) {
        if (self::validateFile($file) !== 0) {
          $form_state->setError($element, t("File '@file' could not be processed. It may not be a valid PDF, or it may be a PDF that is protected or encrypted.", ['@file' => $file->getFilename()]));
        }
      }
    }
  }

  /**
   * Validates a PDF file.
   *
   * We do not accept PDFs that are encrypted or invalid.
   *
   * @param \Drupal\file\Entity\File $file
   *   A file.
   *
   * @return int
   *   The exit code from GhostScript.
   */
  public static function validateFile(File $file) {
    $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());
    // @todo Inject the configuration.
    $config = \Drupal::config('academic_applications.settings');
    $executable = $config->get('ghostscript_path');
    $command = sprintf('%s -dBATCH -sNODISPLAY "%s"', $executable, $filepath);
    $output = [];
    exec($command, $output, $return);
    return $return;
  }

  /**
   * Determines if a file element stores PDFs.
   *
   * @param array $element
   *   A form element.
   *
   * @return bool
   *   True if this form element stores PDFs.
   */
  public static function elementStoresPdf(array $element) {
    return ($element['#type'] == 'managed_file' || $element['#type'] == 'webform_document_file') && $element['#file_extensions'] == 'pdf';
  }

}
