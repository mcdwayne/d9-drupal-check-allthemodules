<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersFilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_hidden",
 *   label = @Translation("Hidden"),
 * )
 */
class Hidden extends BetterExposedFiltersFilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable($filter, $filter_options = []) {

    $bef_standard = FALSE;

    // Check various filter types and determine what options are available.
    if (is_a($filter, 'Drupal\views\Plugin\views\filter\String') || is_a($filter, 'Drupal\views\Plugin\views\filter\InOperator')) {
      if (in_array($filter->operator, ['in', 'or', 'and'])) {
        $bef_standard = TRUE;
      }
      if (in_array($filter->operator, ['empty', 'not empty'])) {
        $bef_standard = TRUE;
      }
    }

    if (is_a($filter, 'Drupal\views\Plugin\views\filter\BooleanOperator')) {
      $bef_standard = TRUE;
    }

    if (is_a($filter, 'Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid')) {
      // Autocomplete and dropdown taxonomy filter are both instances of
      // TaxonomyIndexTid, but we can't show BEF options for the autocomplete
      // widget.
      if ($filter_options['type'] == 'select') {
        $bef_standard = TRUE;
      }
    }
    return $bef_standard;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field, &$show_apply) {
    if (empty($form[$field]['#multiple'])) {
      // Single entry filters can simple be changed to a different element
      // type.
      $form[$field]['#type'] = 'hidden';
    }
    else {
      // Hide the label.
      $form['#info']["filter-$field"]['label'] = '';

      // Use BEF's preprocess and template to output the hidden elements.
      $form[$field]['#theme'] = 'bef_hidden';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state, $label, $filter, $existing) {
    return [];
  }

}
