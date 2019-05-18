<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchFiltersForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\semantic_connector\Api\SemanticConnectorSonrApi;

/**
 * Contribute form.
 */
class PPGraphSearchFiltersForm extends FormBase {
  protected $graphsearch;
  protected $config;
  protected $config_settings;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_filters_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PPGraphSearch $graphsearch = NULL) {
    $this->graphsearch = $graphsearch;
    $this->config = $graphsearch->getConfig();
    $this->config_settings = $this->config->getConfig();

    $form['pp_graphsearch_filters'] = array(
      '#type' => 'container',
      '#prefix' => '<div class="views-exposed-form">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('views-exposed-widgets clearfix'),
      ),
    );

    $filter_list = array();
    foreach ($this->graphsearch->getFilters() as $filter) {
      if ($filter->field == 'date-from' || $filter->field == 'date-to') {
        $filter_list[$filter->field] = $filter->value;
      }
      else {
        $filter_list[] = $filter->value;
      }
    }

    foreach ($this->config_settings['components_order'] as $component_id) {
      switch ($component_id) {
        // Add the facets.
        case 'facets':
          $result = $this->graphsearch->getResult();
          if (is_array($result) && isset($result['facetList'])) {
            // Get all searchable facets.
            $searchable_facets = [];
            foreach ($this->config_settings['facets_to_show'] as $facet) {
              if ($facet['searchable']) {
                $searchable_facets[] = $facet['facet_id'];
              }
            }

            // Display the facets.
            $facet_list = $result['facetList'];
            $facet_nr = 1;
            $push_info = [
              'content_push' => \Drupal::config('pp_graphsearch.settings')->get('content_type_push'),
              'node_types_by_name' => array_flip(node_type_get_names()),
            ];
            foreach ($facet_list as $item) {
              $fieldset_name = 'pp_graphsearch_fieldset_' . $item['field'];
              $tag_id = strtolower(str_replace('_', '-', $item['field']));
              $prefix = '
                <div id="edit-' . $tag_id . '-wrapper" class="views-exposed-widget views-widget-filter-' . $tag_id . '">
                  <div class="views-widget">
                    <div class="form-item form-type-select form-item-' . $tag_id . '">';
              $form['pp_graphsearch_filters'][$fieldset_name] = array(
                '#type' => 'details',
                '#prefix' => $prefix,
                '#suffix' => '</div></div></div>',
                '#title' => $item['label'],
                '#attributes' => array(
                  'class' => array('bef-select-as-checkboxes-fieldset'),
                ),
              );

              $form['pp_graphsearch_filters'][$fieldset_name][$item['field']] = array(
                '#prefix' => '<div id="edit-' . $tag_id . '" class="form-checkboxes">',
                '#suffix' => '</div>',
                '#tree' => TRUE,
              );

              if (in_array($item['field'], $searchable_facets)) {
                $form['pp_graphsearch_filters'][$fieldset_name][$item['field']]['search_box'] = array(
                  '#type' => 'textfield',
                  '#default_value' => '',
                  '#autocomplete_route_name' => 'pp_graphsearch.autocomplete',
                  '#autocomplete_route_parameters' => array(
                    'graphsearch_config' => $this->config->id(),
                    'max_items' => $this->config_settings['ac_max_suggestions'],
                    'facet_id' => $item['field'],
                  ),
                  '#attributes' => array(
                    'class' => array('form-autocomplete', 'facet-autocomplete'),
                    'data-field' => $item['field'],
                    'placeholder' => t($this->config_settings['placeholder']),
                  ),
                );
              }

              $selected = $this->createCheckboxes($form['pp_graphsearch_filters'][$fieldset_name][$item['field']], $item['field'], $item['facets'], $filter_list, $push_info);
              $form['pp_graphsearch_filters'][$fieldset_name]['#collapsed'] = (($facet_nr == 1 && empty($filter_list)) || $selected) ? FALSE : TRUE;
              $facet_nr++;
            }

            // Add the facet to the facet box if the URL parameter uri is given.
            $facet_box = array();
            if (isset($_GET['uri']) || isset($_GET['search'])) {
              foreach ($this->graphsearch->getFilters() as $filter) {
                switch ($filter->field) {
                  case SemanticConnectorSonrApi::ATTR_CONTENT:
                    $facet_box[] = array(
                      'type' => 'free-term',
                      'field' => SemanticConnectorSonrApi::ATTR_CONTENT,
                      'value' => $filter->value,
                      'label' => $filter->value,
                    );
                    break;

                  default:
                    $facet_box[] = array(
                      'type' => 'concept',
                      'field' => $filter->field,
                      'value' => $filter->value,
                      'label' => $filter->label,
                    );
                    break;
                }
              }
            }
            $form['#attached']['drupalSettings']['pp_graphsearch']['facet_box'] = $facet_box;
          }
          break;

        // Add the date filter.
        case 'time':
          if ($this->config_settings['time_filter'] == 'from_to_textfields') {
            $form['pp_graphsearch_filters']['date_from'] = array(
              '#type' => 'date_popup',
              '#title' => t('from'),
              '#default_value' => isset($filter_list['date-from']) ? $filter_list['date-from'][0] : '',
              '#date_format' => 'Y-m-d',
              '#date_label_position' => 'none',
              '#attributes' => array('data-field' => 'date-from'),
              '#date_year_range' => '-10:+1',
            );
            $form['pp_graphsearch_filters']['date_to'] = array(
              '#type' => 'date_popup',
              '#title' => t('to'),
              '#default_value' => isset($filter_list['date-to']) ? $filter_list['date-to'][0] : '',
              '#date_format' => 'Y-m-d',
              '#date_label_position' => 'none',
              '#attributes' => array('data-field' => 'date-to'),
              '#date_year_range' => '-10:+1',
            );
          }
          elseif ($this->config_settings['time_filter'] == 'range_selection') {
            $dates = array(
              'no time limit' => date("Y-m-d", 0),
              'last day' => date("Y-m-d", time() - 60 * 60 * 24),
              'last week' => date("Y-m-d", time() - 60 * 60 * 24 * 7),
              'last month' => date("Y-m-d", time() - 60 * 60 * 24 * 31),
              'last year' => date("Y-m-d", time() - 60 * 60 * 24 * 365),
            );
            $date_options = array();
            foreach ($dates as $date_key => $date) {
              $date_options[$date] = $date_key;
            }
            if (!empty($this->config_settings['time_filter_years'])) {
              $this_year = date('Y');
              for ($year = $this_year; $year >= $this->config_settings['time_filter_years']; $year--) {
                $date_options[$year] = 'in ' . $year;
              }
            }
            $form['pp_graphsearch_filters']['date_from'] = array(
              '#type' => 'select',
              '#prefix' => '<div id="edit-date-from-wrapper" class="views-exposed-widget views-widget-filter-date-from"><div class="views-widget">',
              '#suffix' => '</div></div>',
              '#title' => t('Time range'),
              '#options' => $date_options,
              '#default_value' => isset($filter_list['date-from']) ? $filter_list['date-from'][0] : $dates['no time limit'],
              '#attributes' => array(
                'data-field' => 'date-from',
                'name' => 'date_from[date]'
              ),
            );
          }
          break;

        // Add the reset button.
        case 'reset':
          $form['pp_graphsearch_filters']['reset'] = array(
            '#prefix' => '<div class="views-exposed-widget views-reset-button">',
            '#suffix' => '</div>',
            '#type'   => 'submit',
            '#value'  => 'Reset',
            '#name'   => 'op',
            '#attributes' => array('class' => array('filter-reset')),
          );
          break;

        // Add the trends as a chart if required.
        case 'trends':
          if ($this->config_settings['add_trends']) {
            $this->graphsearch->addTrendsToForm($form, $form_state);
          }
          break;
      }
    }

    // Add additional styling.
    if (!$this->config_settings['separate_blocks']) {
      $form['#attributes']['class'][] = 'pp-graphsearch-area-left';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Creates the checkboxes for the search facets.
   *
   * @param array $form
   *   The facet filter form.
   * @param string $field
   *   The ID for the facet field.
   * @param array $facets
   *   The facets for the facet field.
   * @param array $filter_list
   *   The list of selected filters.
   * @param array $push_info
   *   Associative array of additional information about content pushing.
   * @param int $depth
   *   The depth of the tree for the tree view mode.
   *
   * @return bool
   *   TRUE if one of the filters are found in the facet field, otherwise FALSE.
   */
  protected function createCheckboxes(&$form, $field, $facets, $filter_list, $push_info, $depth = 0) {
    $found = FALSE;
    foreach ($facets as $facet) {
      // Alter the facet labels of custom content.
      if ($field == SemanticConnectorSonrApi::ATTR_CONTENT_TYPE) {
        // Check if there is a custom label for that content type.
        $content_type = '';
        if ($facet['label'] == 'User') {
          $content_type = 'user';
        }
        elseif (in_array($facet['label'], array_keys($push_info['node_types_by_name']))) {
          $content_type = $push_info['node_types_by_name'][$facet['label']];
        }
        if (!empty($content_type) && isset($push_info['content_push'][$content_type]) && isset($push_info['content_push'][$content_type]['label'])) {
          $facet['label'] = $push_info['content_push'][$content_type]['label'];
        }
      }
      $default_value = FALSE;
      if (in_array($facet['value'], $filter_list)) {
        $found = TRUE;
        $default_value = TRUE;
      }
      $form_key = $facet['value'] . '_' . $depth;
      $form[$form_key] = array(
        '#type' => 'checkbox',
        '#title' => $facet['label'] . ' (' . $facet['count'] . ')',
        '#default_value' => $default_value,
        '#return_value' => $facet['value'],
        '#attributes' => array(
          'data-field' => $field,
        ),
        '#prefix' => '<div class="form-item-container">',
        '#suffix' => '</div>',
      );
      if (!empty($facet['children'])) {
        $form[$form_key . '-tree-open'] = array(
          '#markup' => '<div class="tree-depth">',
        );
        if ($this->createCheckboxes($form, $field, $facet['children'], $filter_list, $push_info, $depth + 1)) {
          $form[$form_key]['#suffix'] = '<div class="tree-open-close"></div></div>';
          $form[$form_key . '-tree-open']['#markup'] = '<div class="tree-depth" style="display: block;">';
          $found = TRUE;
        }
        else {
          $form[$form_key]['#suffix'] = '<div class="tree-open-close collapsed"></div></div>';
        }
        $form[$form_key . '-tree-close'] = array(
          '#markup' => '</div>',
        );
      }
    }

    return $found;
  }
}
?>