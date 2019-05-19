<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Better exposed filters filter widget plugins.
 */
interface BetterExposedFiltersFilterWidgetInterface extends PluginInspectionInterface {

  /**
   * Verify this plugin can be used on the from element.
   *
   * @return bool
   *   If this plugin can be used.
   */
  public function label();

  /**
   * Verify this plugin can be used on the form element.
   *
   * @param mixed $filter
   *   The filter type we are altering.
   * @param array $filter_options
   *   The options for this filter.
   *
   * @return bool
   *   If this plugin can be used.
   */
  public function isApplicable($filter, $filter_options = []);

  /**
   * Manipulate views exposed from element.
   *
   * @param array $form
   *   The views configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $field
   *   The field to update.
   * @param bool $show_apply
   *   If the apply button should be shown.
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field, &$show_apply);

  /**
   * Add config elements to BEF form.
   *
   * @param array $form
   *   The views configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $label
   *   The filter label.
   * @param mixed $filter
   *   The filter we are adding extra config for.
   * @param array $existing
   *   The existing config form.
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state, $label, $filter, array $existing);

}
