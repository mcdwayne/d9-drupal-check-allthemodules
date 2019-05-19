<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersFilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends BetterExposedFiltersFilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable($filter, $filter_options = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field, &$show_apply) {
    $show_apply = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state, $label, $filter, $existing) {
    return [];
  }

}
