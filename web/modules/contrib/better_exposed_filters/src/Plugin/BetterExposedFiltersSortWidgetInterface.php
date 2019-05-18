<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Better exposed filters sort widget plugins.
 */
interface BetterExposedFiltersSortWidgetInterface extends PluginInspectionInterface {

  /**
   * The label for this plugin.
   *
   * @return string
   *   The sort plugin label.
   */
  public function label();

  /**
   * Verify this plugin can be used on the from element.
   *
   * @return bool
   *   If this plugin can be used.
   */
  public function isApplicable();

  /**
   * Manipulate the views exposed form.
   *
   * @param array $form
   *   The views exposed form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $field
   *   The field to update.
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field);

  /**
   * Provide a configuration form for this plugin.
   *
   * @param array $form
   *   The views configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state);

}
