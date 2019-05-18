<?php

namespace Drupal\image_styles_mapping\Service;

/**
 * Image Styles Mapping Service interface methods.
 */
interface ImageStylesMappingServiceInterface {

  /**
   * Helper function for image fields.
   *
   * @return array
   *   Display a table of the image styles used in fields.
   */
  public function fieldsReport();

  /**
   * Helper function for views fields.
   *
   * @return array
   *   Display a table of the image styles used in views fields.
   */
  public function viewsFieldsReport();

}
