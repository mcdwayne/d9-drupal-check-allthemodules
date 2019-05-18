<?php

namespace Drupal\selective_better_exposed_filters\Plugin\views\exposed_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\views\Views;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "bef",
 *   title = @Translation("Better Exposed Filters (Selective)"),
 *   help = @Translation("Provides additional options for exposed form elements.")
 * )
 */
class SelectiveBetterExposedFilters extends BetterExposedFilters {

  /**
   * Add to form our options.
   *
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get current settings and default values for new filters.
    $existing = $this->getSettings();

    // Go through each filter and add BEF options.
    /* @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->display_handler->getHandlers('filter') as $label => $filter) {
      if (is_a($filter, 'Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid')) {
        $form['bef'][$label]['more_options']['bef_show_only_used'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show only used terms'),
          '#default_value' => !empty($existing[$label]['more_options']['bef_show_only_used']),
        ];
      }
    }
  }

  /**
   * Add logic to exposed form.
   *
   * @inheritdoc
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    if (empty($this->view->selective_filter)) {
      parent::exposedFormAlter($form, $form_state);

      // Get current settings and default values for new filters.
      $settings = [];
      foreach ($this->getSettings() as $label => $setting) {
        if (!empty($setting['more_options']['bef_show_only_used'])) {
          $settings[$label] = $setting;
        }
      }

      if (!empty($settings)) {
        $view = Views::getView($this->view->id());
        $view->selective_filter = TRUE;
        $view->setArguments($this->view->args);
        $view->setItemsPerPage(0);
        $view->setDisplay($this->view->current_display);
        $view->preExecute();
        $view->execute();

        if (!empty($view->result)) {
          // Shorthand for all filters in this view.
          /* @var \Drupal\views\Plugin\views\HandlerBase[] $filters */
          $filters = $form_state->get('view')->display_handler->handlers['filter'];

          // Go through each saved option looking for Better Exposed Filter settings.
          foreach ($settings as $label => $setting) {
            $filter = &$filters[$label];

            // Sanity check: Ensure this filter is an exposed filter.
            if (empty($filter) || !$filter->isExposed() || empty($filter->configuration['field_name'])) {
              continue;
            }

            $hierarchy = !empty($filter->options['hierarchy']);
            $field_id = $filter->configuration['field_name'];
            $identifier = $filter->options['expose']['identifier'];
            $element = &$form[$identifier];

            $tids = ['All' => 'All'];
            foreach ($view->result as $row) {
              /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
              $entity = $row->_entity;
              if ($entity->hasField($field_id)) {
                $term_values = $entity->get($field_id)->getValue();

                if (!empty($term_values)) {
                  foreach ($term_values as $term_value) {
                    $tid = $term_value['target_id'];
                    $tids[$tid] = $tid;

                    if ($hierarchy) {
                      $parents = \Drupal::service('entity_type.manager')
                        ->getStorage("taxonomy_term")
                        ->loadAllParents($tid);

                      /** @var \Drupal\taxonomy\TermInterface $term */
                      foreach ($parents as $term) {
                        $tids[$term->id()] = $term->id();
                      }
                    }
                  }
                }
              }
            }

            $element['#options'] = array_intersect_key($element['#options'], $tids);
          }
        }
      }
    }
    else {
      $form_state->setUserInput([]);
    }
  }

}
