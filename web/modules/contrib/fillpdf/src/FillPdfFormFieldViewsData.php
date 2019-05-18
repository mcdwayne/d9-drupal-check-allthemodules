<?php

namespace Drupal\fillpdf;

use Drupal\views\EntityViewsData;

/**
 * Class FillPdfFormFieldViewsData.
 *
 * @package Drupal\fillpdf
 */
class FillPdfFormFieldViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fillpdf_fields']['table']['group'] = $data['fillpdf_fields']['table']['base']['title'] = $this->t('FillPDF form fields');

    $data['fillpdf_fields']['table']['base']['help'] = $this->t('FillPDF form fields represent fields in an uploaded FillPDF PDF.');

    return $data;
  }

}
