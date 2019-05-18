<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\Form\HierarchicalConfigurationSettingsForm.
 */

namespace Drupal\hierarchical_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HierarchicalConfigurationSettingsForm.
 *
 * @package Drupal\hierarchical_config\Form
 *
 * @ingroup hierarchical_config
 */
class HierarchicalConfigurationSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'HierarchicalConfiguration_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for Hierarchical configuration entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['HierarchicalConfiguration_settings']['#markup'] = 'Settings form for Hierarchical configuration entities. Manage field settings here.';
    return $form;
  }

}
