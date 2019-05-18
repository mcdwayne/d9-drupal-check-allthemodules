<?php

namespace Drupal\cloud\Service;

/**
 * Interface EntityLinkRendererInterface.
 */
interface EntityLinkRendererInterface {

  /**
   * Render entity link for view.
   *
   * @param string $value
   *   The value of entity.
   * @param string $target_type
   *   The type of target entity.
   * @param string $field_name
   *   The field name of target entity.
   * @param array $query
   *   The query parameters.
   * @param string $alt_text
   *   Optional alternative text to display.
   * @param string $html_generator_class
   *   Html generator class.
   *
   * @return array
   *   The build array of entity link element for viewZ.
   */
  public function renderViewElement(
    $value,
    $target_type,
    $field_name,
    array $query,
    $alt_text = '',
    $html_generator_class = ''
  );

  /**
   * Render entity link for form.
   *
   * @param string $value
   *   The value of entity.
   * @param string $target_type
   *   The type of target entity.
   * @param string $field_name
   *   The field name of target entity.
   * @param array $options
   *   The form element options.
   * @param string $alt_text
   *   Alternative text to display.
   * @param string $html_generator_class
   *   Html generator class.
   *
   * @return array
   *   The build array of entity link element for form.
   */
  public function renderFormElements(
    $value,
    $target_type,
    $field_name,
    array $options,
    $alt_text = '',
    $html_generator_class = ''
  );

}
