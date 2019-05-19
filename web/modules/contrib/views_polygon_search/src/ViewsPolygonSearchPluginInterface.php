<?php

namespace Drupal\views_polygon_search;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a method for creating plugin styles for geo exposed form.
 */
interface ViewsPolygonSearchPluginInterface {

  /**
   * Generate form for plugin settings.
   */
  public function formOptions(&$form, FormStateInterface $form_state, array $options);

  /**
   * Validate plugin style setting form.
   */
  public function validateOptionsForm(&$form, FormState $form_state, $handler);

  /**
   * Submit plugin style setting form.
   */
  public function submitOptionsForm(&$form, FormState $form_state, $handler);

  /**
   * Function for changing exposed form.
   */
  public function valueForm(&$form, FormState $form_state, $handler);

}
