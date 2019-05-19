<?php

namespace Drupal\stacks_content_feed\Plugin\WidgetType;

use Drupal;
use Drupal\stacks\Plugin\WidgetTypeBase;
use Drupal\stacks_content_feed\StacksQuery\StacksDatabaseQuery;
use Drupal\stacks_content_feed\StacksQuery\StacksSolrQuery;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Template\Attribute;
use Drupal\taxonomy\Entity\Term;

/**
 * ContentFeed.
 *
 * @WidgetType(
 *   id = "content_feed",
 *   label = @Translation("Content Feed"),
 * )
 */
class ContentFeed extends WidgetTypeBase {

  /**
   * Responsible for the initial setup for the grid. This includes setting the
   * entity and setting up the grid options for this grid type.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @internal param $widget_entity (object): The stacks entity that has the options.
   * This is sent here from Drupal\stacks_content_feed\Widget\WidgetData::output().
   *
   * $this->grid_options (array)
   *    content_types (array): Available content types to query from.
   *    vocabulary (string): Set a vocabulary machine name if you want to have a
   *      filter that pulls in all terms under a vocabulary.
   *    taxonomy_terms (array): Instead of setting a vocabulary, you can select
   *      certain taxonomy terms.
   *    order_by (string): How to order the query by default: title_asc,
   *      title_desc, created_asc, created_desc
   *    sort (array): Array of available sort options. If the order_by field
   *      comes from an option list, it usually makes sense to grab those options.
   *    enabled_filters (array): Which filters are enabled?
   *    pagination_type (string): Change the type of pagination that is used:
   *      default, mini, load more, load more scroll
   *    per_page (int): If using pagination, how many results per page?
   *    total_results (int): If not using pagination, how many results to display?
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->grid_options = [
      'per_page' => $this->getStringFieldValue('field_cfeed_results_per_page'),
      'content_types' => $this->getArrayFieldValues('field_cfeed_content_types'),
      'vocabulary' => isset($this->getArrayFieldValues('field_cfeed_vocabulary')[0]) ? $this->getArrayFieldValues('field_cfeed_vocabulary') : FALSE,
      'taxonomy_terms' => $this->getArrayFieldValues('field_cfeed_taxonomy_terms'),
      'order_by' => $this->getStringFieldValue('field_cfeed_order'),
      'sticky' => $this->getBooleanFieldValue('field_cfeed_sticky'),
      'sort' => ($this->widget_entity->hasField('field_cfeed_order')) ? $this->widget_entity->get('field_cfeed_order')->getSetting('allowed_values') : 'DESC',
      'enabled_filters' => $this->getArrayFieldValues('field_cfeed_enable_filtering'),
      'pagination_type' => $this->getStringFieldValue('field_cfeed_pagination'),
      'limit_by' => $this->getStringFieldValue('field_cfeed_limit_by'),
    ];

    // If they select a vocabulary for the filter, do not limit the query by
    // taxonomy terms!
    if (!empty($this->grid_options['vocabulary'])) {
      $this->grid_options['taxonomy_terms'] = [];
    }

    $this->setFiltersFrontend();
  }

  /**
   * Modify the render array before output. This is used for the initial display
   * and also all AJAX requests.
   */
  public function modifyRenderArray(&$render_array, $options = []) {
    $active_filters = isset($options['active_filters']) ? $options['active_filters'] : FALSE;
    $is_ajax = isset($options['is_ajax']) ? $options['is_ajax'] : FALSE;

    // Get the results.
    $query_options = [
      'status' => 1,
      'content_types' => $this->grid_options['content_types'],
      'taxonomy' => [
        'ui_selected_tags' => $this->grid_options['taxonomy_terms'],
      ],
      'per_page' => $this->grid_options['per_page'],
      'order_by' => $this->grid_options['order_by'],
      'sticky' => $this->grid_options['sticky'],
      'active_filters' => $active_filters,
      'pagination_type' => $this->grid_options['pagination_type'],
      'limit_by' => $this->grid_options['limit_by'],
    ];

    // Adds results, js/css, and variables to the render array.
    $this->prepareNodeGridAjax($render_array, $is_ajax, $query_options);
  }

  /**
   * If filters are sent, modify the $options array.
   *
   * We take the filters and override the $options in the array to only select
   * the content they want.
   * @param $options
   */
  public function getNodeResultsActiveFilters(&$options) {

    // Put together an array of all fields that are taxonomy fields on the
    // content type(s).
    $tag_fields = [];
    if (isset($options['content_types']) && is_array($options['content_types']) && count($options['content_types'])) {
      $taxonomy_helper = \Drupal::service('stacks.taxonomy_helper');

      $tag_fields = $taxonomy_helper->getTaxonomyFieldsForContentTypes($options['content_types']);
      if (count($tag_fields)) {
        $options['taxonomy_terms_field_names'] = $tag_fields;
      }
    }

    $active_filters = isset($options['active_filters']) ? $options['active_filters'] : [];
    if (count($active_filters) < 1) {
      return;
    }

    // Content Types.
    if (isset($active_filters['content_types']) && count($active_filters['content_types']) > 0) {
      // Make sure the selected filter option is an available option.
      foreach ($active_filters['content_types'] as $content_type) {
        if (!isset($this->grid_options['filters']['content_types'][$content_type])) {
          return;
        }
      }

      $options['content_types'] = $active_filters['content_types'];
    }

    // Taxonomies.
    // Only limit by taxonomy terms if the vocabulary option is not set under filters.
    if (isset($active_filters['taxonomy']) && count($active_filters['taxonomy']) > 0) {

      // Add the taxonomy term ids to an array that is categorized by taxonomy field.
      foreach ($active_filters['taxonomy'] as $vocab => $tids) {
        if (isset($tag_fields[$vocab])) {
          foreach ($tag_fields[$vocab] as $field_name) {
            if (!isset($options['taxonomy'][$field_name])) {
              $options['taxonomy'][$field_name] = [];
            }

            $options['taxonomy'][$field_name] = array_merge($options['taxonomy'][$field_name], $tids);
          }
        }
      } // End terms foreach
    }

    // Sort.
    if (isset($active_filters['sort']) && count($active_filters['sort']) > 0) {
      foreach ($active_filters['sort'] as $sort) {

        // Make sure the selected filter option is an available option.
        if (!isset($this->grid_options['sort'][$sort])) {
          return;
        }

        $options['order_by'] = $sort;
      } // End terms foreach
    }

    // Search.
    if (isset($active_filters['search']) && !empty($active_filters['search'][0])) {
      $options['search'] = trim($active_filters['search'][0]);
    }
  }

  /**
   * Queries the database for nodes.
   *
   * @param $options
   * @param bool $is_ajax
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getNodeResults($options, $is_ajax = FALSE) {
    $use_solr = FALSE;

    // Handle Filter Values.
    $this->getNodeResultsActiveFilters($options);

    if (\Drupal::moduleHandler()->moduleExists('search_api_solr')) {
      $config = \Drupal::service('config.factory')
        ->getEditable('stacks.settings');
      $search_api_index = $config->get("content_feed_search_api_index");
      $fulltext_field = $config->get("content_feed_search_api_fulltext_field");
      if ($search_api_index != NULL && $fulltext_field != NULL) {
        $use_solr = TRUE;
      }
    }

    if ($use_solr == TRUE) {
      $stacks_query = new StacksSolrQuery($this->unique_id, $search_api_index, $fulltext_field);
    }
    else {
      $stacks_query = new StacksDatabaseQuery($this->unique_id);
      $stacks_query->setEntity($this->widget_entity);
    }

    $nids = $stacks_query->getNodeResults($options);
    $data = Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($nids);
    return $data;
  }

  /**
   * Takes a field name and returns a value if it is there. Otherwise NULL. This
   * is meant to be used when a stacks entity is used.
   * @param $field_name
   * @param bool $entity
   * @return string
   */
  public function getStringFieldValue($field_name, $entity = FALSE) {
    if (!$entity) {
      $entity = $this->widget_entity;
    }

    if (!$entity->hasField($field_name)) {
      return '';
    }

    $value = $entity->get($field_name)->getValue();

    return isset($value[0]['value']) ? $value[0]['value'] : '';
  }

  /**
   * Similar to getStringFieldValue(), but meant for boolean fields.
   * @param $field_name
   * @param bool $entity
   * @return bool
   */
  public function getBooleanFieldValue($field_name, $entity = FALSE) {
    if (!$entity) {
      $entity = $this->widget_entity;
    }

    if (!$entity->hasField($field_name)) {
      return FALSE;
    }

    $field_value = $entity->get($field_name)->getValue();
    if (isset($field_value[0]['value'])) {
      $field_value = $field_value[0]['value'];
    }

    $value = FALSE;
    if ($field_value) {
      $value = TRUE;
    }

    return $value;
  }

  /**
   * Takes a multidimensional array and returns an array of values.
   * @param $field_name
   * @param bool $entity
   * @return array|bool
   */
  public function getArrayFieldValues($field_name, $entity = FALSE) {
    if (!$entity) {
      $entity = $this->widget_entity;
    }

    if (!$entity->hasField($field_name)) {
      return [];
    }

    $array = $entity->get($field_name)->getValue();
    $clean_array = [];
    foreach ($array as $value) {
      if (is_array($value)) {
        foreach ($value as $value2) {
          $clean_array[] = $value2;
        }
      }
      else {
        $clean_array[] = $value;
      }
    }

    if (count($clean_array) < 1) {
      return FALSE;
    }

    return $clean_array;
  }

  /**
   * Modify the render array for Content Feed widgets. This is hit when the page
   * first loads and in all ajax requests for this grid.
   *
   * @param $render_array
   * @param $is_ajax
   */
  public function prepareGridAjax(&$render_array, $is_ajax) {

    if (!$this->widget_entity && !$this->unique_id) {
      // If $this->widget_entity is false, we need to make sure a unique
      // widget id is set.
      drupal_set_message(t('Grid widget id need to be set.'), 'error');
      return;
    }

    if ($this->widget_entity) {
      // We need to set the entity object. Since WidgetData->output() isn't hit
      // for with the ajax request, we need to set it.
      $render_array['#widget_entity'] = [
        'entity_id' => $this->widget_entity->id(),
        'entity_type' => $this->widget_entity->getEntityTypeId(),
        'entity_bundle' => $this->widget_entity->bundle(),
      ];
    }
    else {
      // This is not a stacks entity. Set some values.
      $render_array['#widget_entity'] = [
        'entity_id' => $this->unique_id,
      ];

      if (!$is_ajax) {
        // Define which template to use. This should be set in child class.
        $render_array['#theme'] = $this->template;
      }
    }

    // If this is not an ajax request, we need to send ajax_attributes and
    // attach the necessary JS/CSS. The key for each attribute should not
    // contain underscores.
    if (!$is_ajax) {
      $render_array['#grid']['ajax_attributes'] = new Attribute([
        'id' => 'grid_widget_' . $this->unique_id,
        'widgetid' => $this->unique_id,
        'typeofgrid' => 'contentfeed',
        // Ajax request theme call.
        'theme' => str_replace('contentfeed', 'ajax_contentfeed', $render_array['#theme']),
        'isentity' => ($this->widget_entity) ? 1 : 0,
        // If this is not a stacks entity, the value will be set by the class
        // with the path to the object.
        'notentity' => '',
      ]);

      $render_array['#attached']['library'][] = 'stacks_content_feed/grid.ajax';
    }
  }

  /**
   * Call this method when returning nodes in your modifyRenderArray(). This
   * adds the results, the necessary JS/CSS and variables for ajax calls.
   * @param $render_array
   * @param $is_ajax
   * @param $query_options
   */
  public function prepareNodeGridAjax(&$render_array, $is_ajax, $query_options) {
    // Adds js/css, sets attributes, etc...
    $this->prepareGridAjax($render_array, $is_ajax);

    // Adding filters to set All terms when restricting terms.
    if (!$query_options['active_filters'] || count($query_options['active_filters']) == 0 || !isset($query_options['active_filters']['taxonomy'])) {
      $cfeed_taxonomy_terms_field = $this->widget_entity->get('field_cfeed_taxonomy_terms')->getValue();

      if (count($cfeed_taxonomy_terms_field) > 0) {
        // Pulling the right taxonomy vocabularies to rebuild
        // the filters when 'All' is selected.
        foreach ($cfeed_taxonomy_terms_field as $tid) {
          $term_detail = taxonomy_term_load($tid['target_id']);
          if (isset($term_detail)) {
            $query_options['active_filters']['taxonomy'][$term_detail->getVocabularyId()][] = $tid['target_id'];
          }
        }
      }
    }

    $render_array['#grid']['results'] = $this->getNodeResults($query_options, $is_ajax);
    $render_array['#grid']['filters'] = isset($this->grid_options['filters']) ? $this->grid_options['filters'] : [];
    $render_array['#grid']['default_sort'] = isset($this->grid_options['order_by']) ? $this->grid_options['order_by'] : 'title_asc';
    $render_array['#grid']['pagination_type'] = isset($this->grid_options['pagination_type']) ? $this->grid_options['pagination_type'] : '';

    $render_array['#attached']['drupalSettings']['stacksgrid']['scroll'] = [];
    if ($render_array['#grid']['pagination_type'] == 'load_more_scroll') {
      $render_array['#attached']['drupalSettings']['stacksgrid']['scroll'][$this->unique_id] = $render_array['#grid']['pagination_type'];
    }
  }

  /**
   * Is called with the ajax controller is hit.
   *
   * @See: Drupal\stacks_content_feed\\GridController
   *
   * @param $render_array
   * @param $active_filters
   * @return AjaxResponse() object.
   */
  public function doAjax($render_array, $active_filters) {

    $this->modifyRenderArray($render_array, [
      'active_filters' => $active_filters,
      'is_ajax' => TRUE,
    ]);

    // This is the id of the ajax_results div.
    // When you do ajax respond commands. It runs against the whole document.
    $wrapper_id = '#grid_widget_' . $this->unique_id;

    $response = new AjaxResponse();

    if (isset($this->grid_options['pagination_type']) && ($this->grid_options['pagination_type'] == 'load_more' || $this->grid_options['pagination_type'] == 'load_more_scroll')) {
      // Remove the previous load more button.
      $response->addCommand(new InvokeCommand($wrapper_id . ' .ajax_results_pagination', 'remove'));

      // Append Results
      // Don't send a selector. Let the JS figure out where it should go. This
      // should use gridAjaxSettings.wrapper set in the JS.
      $response->addCommand(new AppendCommand(NULL, $render_array));
    }
    else {
      // Replace Results
      // Don't send a selector. Let the JS figure out where it should go. This
      // should use gridAjaxSettings.wrapper set in the JS.
      $response->addCommand(new HtmlCommand(NULL, $render_array));
    }

    // Trigger the equalizer js. See grid.ajax.js.
    $response->addCommand(new InvokeCommand($wrapper_id, 'ajaxequalizer'));

    return $response;
  }

  /**
   * Puts together the arrays for the filters variable in the template.
   */
  protected function setFiltersFrontend() {
    $taxonomy_helper = \Drupal::service('stacks.taxonomy_helper');
    $filters = [];

    // Loop through the enabled filters. Grab the correct grid_option values
    // and use that to populate the available options.
    $enabled_filters = $this->grid_options['enabled_filters'];

    if (is_array($enabled_filters)) {
      foreach ($enabled_filters as $filter) {

        // Loop through each option. Options are based on grid_options.
        $filter_options = isset($this->grid_options[$filter]) ? $this->grid_options[$filter] : FALSE;
        if ($filter_options && count($filter_options) > 0) {
          foreach ($filter_options as $key => $filter_options_row) {
            $value = ucwords($filter_options_row);
            if ($filter == 'taxonomy_terms') {
              // Load Taxonomy Term Title.
              $term = Term::load($value);
              $value = $term->getName();
            }
            elseif ($filter == 'content_types') {
              $content_type = Drupal\node\Entity\NodeType::load($filter_options_row);
              $value = $content_type->get('name');
            }
            else {
              if ($filter == 'sort') {
                $filter_options_row = $key;
              }
            }

            $filters[$filter][$filter_options_row] = $value;

          } // End $filter_options foreach
        }
        else {

          // These filters don't have options.
          $filters[$filter] = 1;

        } // End $filter_options if
      } // End $enabled_filters foreach
    } // End main if
    // If taxonomy filter is enabled and if they set a vocabulary, populate
    // the taxonomy terms from the vocabulary.
    if ($this->grid_options['vocabulary'] && is_array($enabled_filters) && in_array('taxonomy_terms', $enabled_filters)) {
      // Adding parameters to remove elements from the vocabulary select field
      // if we don't use all the terms.
      $cfeed_taxonomy_terms = [];
      $cfeed_taxonomy_terms_field = $this->widget_entity->field_cfeed_taxonomy_terms->getValue();
      if (count($cfeed_taxonomy_terms_field) > 0) {
        foreach ($cfeed_taxonomy_terms_field as $taxonomy_term) {
          $cfeed_taxonomy_terms[] = $taxonomy_term['target_id'];
        }
      }

      foreach ($this->grid_options['vocabulary'] as $vocab) {
        $taxonomy_helper->getTermsFromVocab($filters, $vocab, $cfeed_taxonomy_terms);
      }
    }

    $this->grid_options['filters'] = $filters;
  }

  /**
   * Helper function for returning an ajax request. This makes it simpler to
   * debug AJAX issues. If user is logged in, displays $_POST variables.
   * @param $message
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  static public function postAjaxErrorMessage($message) {
    if (!Drupal::currentUser()->isAnonymous()) {
      $message .= '<pre>' . print_r($_POST, TRUE) . '</pre>';
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(FALSE, ['#markup' => $message]));

    return $response;
  }

  /**
   * Define the fields that should not be sent to the template as variables.
   * These are usually fields on the bundle that you want to handle via
   * programming only.
   */
  public function fieldExceptions() {
    return [
      'field_cfeed_content_types',
      'field_cfeed_enable_filtering',
      'field_cfeed_limit_by',
      'field_cfeed_order',
      'field_cfeed_pagination',
      'field_cfeed_results_per_page',
      'field_cfeed_sticky',
      'field_cfeed_taxonomy_terms',
      'field_cfeed_vocabulary',
    ];
  }

}
