<?php

namespace Drupal\academic_applications;

use Drupal\webform\WebFormInterface;
use Drupal\webform\WebFormSubmissionInterface;
use Drupal\academic_applications\Validator\PdfValidator;

/**
 * Class SubmissionPdfFinder finds PDFs in form submissions.
 */
class SubmissionPdfFinder {

  /**
   * Gets the file IDs of PDFs in a form submission.
   *
   * @param \Drupal\webform\WebFormSubmissionInterface $webFormSubmission
   *   A Webform submission.
   *
   * @return array
   *   File IDs.
   */
  public function getFileIds(WebFormSubmissionInterface $webFormSubmission) {
    $file_ids = [];
    $pdf_elements = $this->getManagedFileElements($webFormSubmission->getWebform());
    $submission_data = $webFormSubmission->getData();
    foreach ($pdf_elements as $element) {
      if (in_array($element['#webform_key'], array_keys($submission_data))) {
        $file_ids[] = $submission_data[$element['#webform_key']];
      }
    }
    return $file_ids;
  }

  /**
   * Gets form elements that store PDFs in a form submission.
   *
   * @param \Drupal\webform\WebFormInterface $webForm
   *   A Webform submission.
   *
   * @return array
   *   Form elements that store PDFs.
   */
  protected function getManagedFileElements(WebFormInterface $webForm) {
    $pdf_elements = [];
    foreach ($webForm->getElementsInitializedAndFlattened() as $element) {
      if (PdfValidator::elementStoresPdf($element)) {
        $pdf_elements[] = $element;
      }
    }
    return $pdf_elements;
  }

}
