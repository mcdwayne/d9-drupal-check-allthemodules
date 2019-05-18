<?php

namespace Drupal\consent\Oil;

/**
 * Configuration builder interface for the OIL.js framework.
 */
interface OilConfigBuilderInterface {

  /**
   * Get all possible parameters of the OIL configuration.
   *
   * @return array
   *   The configuration parameters.
   */
  public function availableParameters();

  /**
   * Build the OIL configuration tag as render array.
   *
   * @param array $values
   *   The param values to use as OIL config.
   *
   * @return array
   *   A renderable array for printing the configuration tag.
   */
  public function buildConfigTag(array $values);

  /**
   * Get the config form elements to embed into a form.
   *
   * @param array $values
   *   The param values to use as OIL config.
   *
   * @return array
   *   The form elements as render array.
   */
  public function configFormElements(array $values);

  /**
   * Get the default configuration values.
   *
   * @return array
   *   The default configuration values.
   */
  public function defaultValues();

  /**
   * Get the default publicPath param value.
   *
   * @return string
   *   The default publicPath param value.
   */
  public function defaultPublicPath();

  /**
   * Get the OIL.js script source.
   *
   * @return string
   *   The OIL.js script source.
   */
  public function scriptSource();

}
