<?php

namespace Drupal\gtm_datalayer;

/**
 * Defines the interface for GTM dataLayer Renderers.
 */
interface RendererInterface {

  /**
   * Gets renderer values.
   *
   * @return array $values
   *   The values used to extract the tags.
   */
  public function getValues();

  /**
   * Sets renderer values.
   *
   * @param array $values
   *   The values used to extract the tags.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Renders dataLayer tags.
   *
   * @param array $values
   *   The values to be used to extract the tags.
   *
   * @return array
   *   The rendered dataLayer tags.
   */
  public function render(array $values);

}
