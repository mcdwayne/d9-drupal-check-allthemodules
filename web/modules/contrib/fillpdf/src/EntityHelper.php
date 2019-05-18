<?php

namespace Drupal\fillpdf;

/**
 * Class EntityHelper.
 *
 * @package Drupal\fillpdf
 * @deprecated This class and the 'fillpdf.entity_helper' service is deprecated
 *   in FillPDF 8.x-4.7 and will be removed before FillPDF 8.x-5.0.
 *   The getFormFields() method lives within the FillPdfForm entity now.
 * @see \Drupal\fillpdf\Entity\FillPdfForm::getFormFields()
 */
class EntityHelper implements EntityHelperInterface {

  /**
   * Gets all FillPdfFormFields associated with a FillPdfForm.
   *
   * @param \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form
   *   The FillPdfForm.
   *
   * @return \Drupal\fillpdf\FillPdfFormFieldInterface[]
   *   Array of all associated FillPdfFormFields.
   */
  public function getFormFields(FillPdfFormInterface $fillpdf_form) {
    return $fillpdf_form->getFormFields();
  }

}
