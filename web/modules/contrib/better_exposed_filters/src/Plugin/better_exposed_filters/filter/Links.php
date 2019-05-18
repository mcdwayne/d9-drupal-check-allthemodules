<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersFilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_links",
 *   label = @Translation("Links"),
 * )
 */
class Links extends BetterExposedFiltersFilterWidgetBase {

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
    $form[$field]['#theme'] = 'bef_links';
    // Exposed form displayed as blocks can appear on pages other than
    // the view results appear on. This can cause problems with
    // select_as_links options as they will use the wrong path. We
    // provide a hint for theme functions to correct this.
    $form[$field]['#bef_path'] = $this->getExposedFormActionUrl($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state, $label, $filter, $existing) {
    if (!empty($bef_options[$label]['more_options']['bef_select_all_none'])) {
      $bef_options[$label]['more_options']['bef_select_all_none'] = [
        '#type' => 'checkbox',
        '#title' => t('Add select all/none links'),
        '#default_value' => $existing[$label]['more_options']['bef_select_all_none'],
        '#disabled' => !$filter->options['expose']['multiple'],
        '#description' => t(
          'Add a "Select All/None" link when rendering the exposed filter using
              checkboxes. If this option is disabled, edit the filter and check the
              "Allow multiple selections".'
        ),
      ];
    }

    if (!empty($bef_options[$label]['more_options']['bef_collapsible'])) {
      // Put filter in details element option.
      // TODO: expand to all exposed filters.
      $bef_options[$label]['more_options']['bef_collapsible'] = [
        '#type' => 'checkbox',
        '#title' => t('Make this filter collapsible'),
        '#default_value' => $existing[$label]['more_options']['bef_collapsible'],
        '#description' => t(
          'Puts this filter in a collapsible details element'
        ),
      ];
    }
  }

  /**
   * Returns exposed form action URL object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Exposed views form state.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  protected function getExposedFormActionUrl(FormStateInterface $form_state) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->get('view');
    $display = $form_state->get('display');

    if (isset($display['display_options']['path'])) {
      return Url::fromRoute(implode('.', [
        'view',
        $view->id(),
        $display['id'],
      ]));
    }

    $request = \Drupal::request();
    $url = Url::createFromRequest($request);
    $url->setAbsolute();

    return $url;
  }

}
