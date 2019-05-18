<?php

namespace Drupal\dream_fields;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * An interface for dream field plugins.
 */
interface DreamFieldPluginInterface extends PluginInspectionInterface {

  /**
   * Return the label.
   */
  public function getLabel();

  /**
   * Get a form to assist in field creation.
   *
   * @return array
   *   A form array.
   */
  public function getForm();

  /**
   * Validate the plugin form.
   *
   * @param array $values
   *   The values from the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm($values, FormStateInterface $form_state);

  /**
   * Save the plugin from.
   *
   * @param array $values
   *   An array of values from the ::getForm method.
   * @param \Drupal\dream_fields\FieldBuilderInterface $field_builder
   *   A field builder with context for creating fields.
   */
  public function saveForm($values, FieldBuilderInterface $field_builder);

}
