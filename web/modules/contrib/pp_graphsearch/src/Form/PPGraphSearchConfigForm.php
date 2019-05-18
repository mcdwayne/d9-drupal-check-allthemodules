<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchConfigForm.
 */

namespace Drupal\pp_graphsearch\Form;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;

class PPGraphSearchConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var PPGraphSearchConfig $entity */
    $entity = $this->entity;

    $configuration = $entity->getConfig();

    $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
    $overridden_values = array();
    if (isset($connection_overrides[$entity->id()])) {
      $overridden_values = $connection_overrides[$entity->id()];
    }

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the PoolParty GraphSearch configuration.'). (isset($overridden_values['title']) ? ' <span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>' : ''),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $entity->getTitle(),
      '#required' => TRUE,
    );

    /** @var SemanticConnectorPPServerConnection $connection */
    $connection = $entity->getConnection();
    // Get the search space label.
    $search_space_id = $entity->getSearchSpaceId();
    $connection_config = $connection->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $search_space_label = t('<invalid search space selected>');
    if (is_array($graphsearch_config)) {
      if (is_array($graphsearch_config)) {
        if (version_compare($graphsearch_config['version'], '6.1', '>=')) {
          $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
          foreach ($search_spaces as $search_space) {
            if ($search_space['id'] == $search_space_id) {
              $search_space_label = $search_space['name'];
              break;
            }
          }
        }
        else {
          $projects = $connection->getApi('PPT')->getProjects();
          foreach ($projects as $project) {
            if (isset($graphsearch_config['projects'][$project['id']]) && $project['id'] == $search_space_id) {
              $search_space_label = $project['title'];
              break;
            }
          }
        }
      }
    }

    // Add information about the connection.
    $connection_markup = '';
    // Check the PoolParty server version if required.
    if (\Drupal::config('semantic_connector.settings')->get('version_checking')) {
      $version_messages = array();

      $ppx_api_version_info = $connection->getVersionInfo('PPX');
      if (version_compare($ppx_api_version_info['installed_version'], $ppx_api_version_info['latest_version'], '<')) {
        $version_messages[] = t('The connected PoolParty server is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $ppx_api_version_info['installed_version'], '%latestversion' => $ppx_api_version_info['latest_version']));
      }

      $sonr_api_version_info = $connection->getVersionInfo('sonr');
      if (version_compare($sonr_api_version_info['installed_version'], $sonr_api_version_info['latest_version'], '<')) {
        $version_messages[] = t('The connected PoolParty GraphSearch server is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $sonr_api_version_info['installed_version'], '%latestversion' => $sonr_api_version_info['latest_version']));
      }

      if (!empty($version_messages)) {
        $connection_markup .= '<div class="messages warning"><div class="message">' . implode('</div><div class="message">', $version_messages) . '</div></div>';
      }
    }
    $connection_markup .= '<p id="pp-graphsearch-connection-info">' . t('Connected PoolParty server') . ': <b>' . $connection->getTitle() . ' (' . $connection->getUrl() . ')</b><br />'
      . t('Selected search space') . ': <b>' . $search_space_label . '</b><br />'
      . Link::fromTextAndUrl(t('Change the connected PoolParty server or search space'), Url::fromRoute('entity.pp_graphsearch.edit_form', array('pp_graphsearch' => $entity->id())))->toString() . '</p>';
    $form['pp_connection_markup'] = array(
      '#type' => 'markup',
      '#markup' => $connection_markup,
    );

    // Define the container for the vertical tabs.
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
    );

    // Tab: Result settings.
    $form['result_settings'] = array(
      '#type' => 'details',
      '#title' => t('Result settings'),
      '#group' => 'settings',
    );

    $form['result_settings']['items_per_page'] = array(
      '#type' => 'textfield',
      '#title' => t('Items per page'),
      '#description' => t('The number of items you want to display on the first result page.'),
      '#size' => 15,
      '#maxlength' => 5,
      '#default_value' => $configuration['items_per_page'],
      '#required' => TRUE,
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    $form['result_settings']['show_results_count'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show results count'),
      '#description' => t('This option adds a total count of results for the current selection above the results list.'),
      '#default_value' => $configuration['show_results_count'],
    );

    $form['result_settings']['result_row_settings'] = array(
      '#type' => 'fieldset',
      '#title' => 'Result item display',
    );

    $form['result_settings']['result_row_settings']['summary_max_chars'] = array(
      '#type' => 'textfield',
      '#title' => t('Length of the summary'),
      '#description' => t('The maximum length in characters you want to display on every result item.'),
      '#size' => 15,
      '#maxlength' => 5,
      '#default_value' => $configuration['summary_max_chars'],
      '#required' => TRUE,
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    $date_types = DateFormat::loadMultiple();
    $date_formatter = \Drupal::service('date.formatter');
    $current_time = \Drupal::time()->getRequestTime();
    $date_options = [];
    /**
     * @var string $machine_name
     * @var DateFormat $format
     */
    foreach ($date_types as $machine_name => $format) {
      $date_options[$machine_name] = t('@name format', array('@name' => $format->label())) . ' --> "' .$date_formatter->format($current_time, $machine_name) . '"';
    }

    $form['result_settings']['result_row_settings']['date_format'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#options' => $date_options,
      '#description' => t('The format the date gets displayed in. You can configure your date types ') . Link::fromTextAndUrl(t('here'), Url::fromRoute('entity.date_format.collection'))->toString() . '.',
      '#default_value' => $configuration['date_format'],
    );

    $form['result_settings']['result_row_settings']['link_target'] = array(
      '#type' => 'select',
      '#title' => t('Link target'),
      '#options' => [
        '_blank' => t('"_blank" --> Load in a new window'),
        '_self' => t('"_self" --> Load in the same frame as it was clicked'),
        '_top' => t('"_top" --> Load in the full body of the window'),
      ],
      '#description' => t('The place where to open an article clicked on by a user.'),
      '#default_value' => $configuration['link_target'],
    );

    $form['result_settings']['result_row_settings']['show_sentiment'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show dynamic sentiment'),
      '#description' => t('This option specifies whether dynamic sentiments should be displayed or not'),
      '#default_value' => $configuration['show_sentiment'],
    );

    $form['result_settings']['result_row_settings']['show_tags'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show a list of all tagged concepts for every content item.'),
      '#description' => t('This option specifies whether tags should be displayed or not'),
      '#default_value' => $configuration['show_tags'],
    );

    $form['result_settings']['result_row_settings']['tags_max_items_container'] = array(
      '#type' => 'container',
      '#attributes' => array('style' => array('padding-left:18px;')),
    );

    $form['result_settings']['result_row_settings']['tags_max_items_container']['tags_max_items'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of tags'),
      '#description' => t('The maximum number of tags you want to display. Leave empty to display all tags.'),
      '#size' => 15,
      '#maxlength' => 3,
      '#default_value' => $configuration['tags_max_items'],
      '#element_validate' => array('::element_validate_integer_positive'),
      '#states' => array(
        'visible' => array(':input[name="show_tags"]' => array('checked' => TRUE)),
      ),
    );

    $form['result_settings']['result_row_settings']['show_similar'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show similar documents'),
      '#description' => t('This specifies whether the more like this option should be displayed or not'),
      '#default_value' => $configuration['show_similar'],
    );

    $form['result_settings']['result_row_settings']['similar_max_items_container'] = array(
      '#type' => 'container',
      '#attributes' => array('style' => array('padding-left:18px;')),
    );

    $form['result_settings']['result_row_settings']['similar_max_items_container']['similar_max_items'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of documents'),
      '#description' => t('The maximum number of similar documents you want to display.'),
      '#size' => 15,
      '#maxlength' => 3,
      '#default_value' => $configuration['similar_max_items'],
      '#element_validate' => array('::element_validate_integer_positive'),
      '#states' => array(
        'visible' => array(':input[name="show_similar"]' => array('checked' => TRUE)),
        'required' => array(':input[name="show_similar"]' => array('checked' => TRUE)),
      ),
    );

    $form['result_settings']['advanced_settings'] = array(
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#open' => FALSE,
    );

    $form['result_settings']['advanced_settings']['first_page_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('First page only'),
      '#description' => t('This option disables infinite loading as well as any kind of pagination.'),
      '#default_value' => $configuration['first_page_only'],
    );

    $intervals = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $formatter = \Drupal::service('date.formatter');
    $period = array_map(array($formatter, 'formatInterval'), array_combine($intervals, $intervals));
    $period[0] = '<' . t('none') . '>';
    $form['result_settings']['advanced_settings']['cache_lifetime'] = array(
      '#type' => 'select',
      '#title' => t('Minimum cache lifetime for the first result page'),
      '#description' => t('Cached result pages will not be re-created until at least this much time has elapsed.'),
      '#options' => $period,
      '#default_value' => $configuration['cache_lifetime'],
    );

    // Tab: Filter settings.
    $form['filter_settings'] = array(
      '#type' => 'details',
      '#title' => t('Filter settings'),
      '#group' => 'settings',
    );

    $form['filter_settings']['separate_blocks'] = array(
      '#type' => 'checkbox',
      '#title' => t('Separate blocks for filters and the result list'),
      '#description' => t('If this option is selected, two blocks will be created. This offers more flexibility in positioning the filters-area or even hiding the filters completely.'),
      '#default_value' => $configuration['separate_blocks'],
    );

    $aggregator = new PPGraphSearch($entity);
    $all_facets = $aggregator->getAllFacets();
    $stored_facets = array();
    $facets = array();
    foreach ($configuration['facets_to_show'] as $weight => $facet) {
      // Manage new facet data.
      if (is_array($facet)) {
        // Ignore removed facets on PoolParty GraphSearch server.
        if (!isset($all_facets[$facet['facet_id']])) {
          continue;
        }
        $facets[$weight] = $facet['facet_id'];
        $stored_facets[$facet['facet_id']] = $facet;
      }
      // Manage old facet data.
      else {
        // Ignore removed facets on PoolParty GraphSearch server.
        if (!isset($all_facets[$facet])) {
          continue;
        }
        $facets[$weight] = $facet;
        $stored_facets[$facet] = array(
          'facet_id' => $facet,
          'name' => '',
          'selected' => TRUE,
          'facet_mode' => 'list',
          'tree_depth' => 0,
          'max_items' => 10,
          'searchable' => FALSE,
        );
      }
    }
    if (is_array($all_facets)) {
      foreach (array_keys($all_facets) as $facet_id) {
        if (!in_array($facet_id, $facets)) {
          $facets[] = $facet_id;
          $stored_facets[$facet_id] = array(
            'facet_id' => $facet_id,
            'name' => $all_facets[$facet_id],
            'selected' => FALSE,
            'facet_mode' => 'list',
            'tree_depth' => 0,
            'max_items' => 10,
            'searchable' => FALSE,
          );
        }
      }
    }

    $form['filter_settings']['facets_to_show'] = array(
      '#type' => 'table',
      '#header' => array(t('Facet name'), t('Show'), t('Filter name'), t('Facet mode'), t('Maximum terms'), t('Hierarchical depth'), t('Searchable'), t('Weight')),
      '#empty' => t('There are no facets available.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'facets-to-show-order-weight',
        ),
      ),
      '#tree' => TRUE,
    );

    foreach ($facets as $weight => $facet_id) {
      // TableDrag: Mark the table row as draggable.
      $form['filter_settings']['facets_to_show'][$facet_id]['#attributes']['class'][] = 'draggable';

      //$form['filter_settings']['facets_to_show']['#tree'] = TRUE;
      $form['filter_settings']['facets_to_show'][$facet_id]['node'] = array(
        '#markup' => $all_facets[$facet_id],
      );

      $form['filter_settings']['facets_to_show'][$facet_id]['#weight'] = $weight;
      $form['filter_settings']['facets_to_show'][$facet_id]['selected'] = array(
        '#type' => 'checkbox',
        '#default_value' => $stored_facets[$facet_id]['selected'],
      );
      $form['filter_settings']['facets_to_show'][$facet_id]['name'] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#maxlength' => 255,
        '#default_value' => isset($stored_facets[$facet_id]['name']) ? $stored_facets[$facet_id]['name'] : $all_facets[$facet_id],
      );
      $form['filter_settings']['facets_to_show'][$facet_id]['facet_mode'] = array(
        '#type' => 'select',
        '#options' => array(
          'list' => t('flat'),
          'aggregate' => t('top concepts'),
          'tree' => t('hierarchical'),
        ),
        '#default_value' => $stored_facets[$facet_id]['facet_mode'],
      );
      $form['filter_settings']['facets_to_show'][$facet_id]['max_items'] = array(
        '#type' => 'textfield',
        '#element_validate' => array('::element_validate_integer_positive'),
        '#size' => 5,
        '#maxlength' => 3,
        '#default_value' => $stored_facets[$facet_id]['max_items'],
      );
      $form['filter_settings']['facets_to_show'][$facet_id]['tree_depth'] = array(
        '#type' => 'select',
        '#options' => array(0, 1, 2, 3),
        '#default_value' => $stored_facets[$facet_id]['tree_depth'],
        '#states' => array(
          'enabled' => array(':input[name="facets_to_show[' . $facet_id . '][facet_mode]"]' => array('value' => 'tree')),
        ),
      );
      $form['filter_settings']['facets_to_show'][$facet_id]['searchable'] = array(
        '#type' => 'checkbox',
        '#default_value' => $stored_facets[$facet_id]['searchable'],
        '#disabled' => (substr($facet_id, 0, 7) == 'dyn_lit'),
      );

      // This field is invisible, but contains sort info (weights).
      $form['filter_settings']['facets_to_show'][$facet_id]['weight'] = array(
        '#type' => 'weight',
        // Weights from -255 to +255 are supported because of this delta.
        '#delta' => 255,
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => array('class' => array('facets-to-show-order-weight')),
      );
    }

    $form['filter_settings']['hide_empty_facet'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide facets without documents'),
      '#description' => t('If it\'s checked, the facets without documents will be hidden, otherwise it will be shown greyed out.'),
      '#default_value' => $configuration['hide_empty_facet'],
    );

    $form['filter_settings']['time_filter'] = array(
      '#type' => 'select',
      '#title' => t('Date filter format'),
      '#options' => array(
        'from_to_textfields' => t('Separate textfields for "from" and "to" date values'),
        'range_selection' => t('The user can select a time range (last day, last month, ...)'),
      ),
      '#empty_option' => t('No date filter'),
      '#empty_value' => NULL,
      '#default_value' => $configuration['time_filter'],
    );

    $form['filter_settings']['time_filter_years'] = array(
      '#type' => 'textfield',
      '#title' => t('Date filter with years'),
      '#description' => t('The year must be less then @year. Leave empty if no years should be displayed in the filter.', array('@year' => date('Y'))),
      '#field_prefix' => t('Add years to the time range filter from this year to') . ' ',
      '#field_suffix' => t('(e.g. 2012)'),
      '#default_value' => $configuration['time_filter_years'],
      '#maxlength' => 4,
      '#attributes' => array('style' => array('width:50px')),
      '#element_validate' => array('::element_validate_integer_positive'),
      '#states' => array(
        'visible' => array(':input[name="time_filter"]' => array('value' => 'range_selection')),
      ),
    );

    if (!\Drupal::moduleHandler()->moduleExists('date_popup')) {
      $link = Link::fromTextAndUrl('Date', Url::fromUri('https://www.drupal.org/project/date', array('attributes' => array('target' => '_blank'))))->toString();
      $hidden = $configuration['time_filter'] == 'from_to_textfields' ? '' : 'hidden';
      $form['filter_settings']['date_info'] = array(
        '#markup' => t('Please install the module @date and enable Date and Date Popup to use this feature.', array('@date' => $link)),
        '#prefix' => '<div id="edit-date-info" class="form-item ' . $hidden . '"><b>' . t('The module Date Popup is missing') . '</b><br />',
        '#suffix' => '</div>',
      );
    }

    $components = array(
      'facets' => t('Facets filter'),
      'time' => t('Date filter'),
      'reset' => t('Reset button'),
    );
    if (PPGraphSearch::isFlotInstalled()) {
      $components['trends'] = t('Trends chart');
    }
    $weighted_components = $configuration['components_order'];
    foreach (array_keys($components) as $component_id) {
      if (!in_array($component_id, $weighted_components)) {
        $weighted_components[] = $component_id;
      }
    }

    $form['filter_settings']['components_order'] = array(
      '#type' => 'table',
      '#header' => array('Order', t('Component name'), t('Weight')),
      '#empty' => t('There are no components available.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'components-order-order-weight',
        ),
      ),
      '#tree' => TRUE,
    );

    foreach ($weighted_components as $weight => $component_id) {
      if (!isset($components[$component_id])) {
        continue;
      }
      // TableDrag: Mark the table row as draggable.
      $form['filter_settings']['components_order'][$component_id]['#attributes']['class'][] = 'draggable';

      $form['filter_settings']['components_order'][$component_id]['order-cell'] = array(
        '#markup' => '',
        '#attributes' => array('class' => array('slide-cross')),
      );

      //$form['filter_settings']['components_order']['#tree'] = TRUE;
      $form['filter_settings']['components_order'][$component_id]['node'] = array(
        '#markup' => $components[$component_id],
      );

      $form['filter_settings']['components_order'][$component_id]['#weight'] = $weight;
      // This field is invisible, but contains sort info (weights).
      $form['filter_settings']['components_order'][$component_id]['weight'] = array(
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => array('class' => array('components-order-order-weight')),
      );
    }

    // Tab: Search bar settings.
    $form['searchbar_settings'] = array(
      '#type' => 'details',
      '#title' => t('Search bar settings'),
      '#group' => 'settings',
    );

    $form['searchbar_settings']['show_searchbar'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the search bar'),
      '#description' => t('Check it if the search bar with the buttons "Search" and "Reset" should be displayed over the result list.'),
      '#default_value' => $configuration['show_searchbar'],
    );

    $form['searchbar_settings']['show_block_searchbar'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create an additional block with the search bar'),
      '#description' => t('This checkbox creates an additional block with the search bar, which can be added into e.g. the header area.'),
      '#default_value' => $configuration['show_block_searchbar'],
    );

    $form['searchbar_settings']['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#description' => t('The placeholder is displayed in the input field before the user enters a value.'),
      '#size' => 15,
      '#default_value' => $configuration['placeholder'],
    );

    $form['searchbar_settings']['ac_min_chars'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of characters'),
      '#description' => t('The minimum number of characters that must be entered before the auto completion is performed (must be greater than 1).'),
      '#size' => 15,
      '#maxlength' => 3,
      '#default_value' => $configuration['ac_min_chars'],
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    $form['searchbar_settings']['suggestions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Suggestions'),
    );

    $form['searchbar_settings']['suggestions']['ac_max_suggestions'] = array(
      '#type' => 'textfield',
      '#title' => t('Count of suggestions'),
      '#description' => t('The maximum number of terms that should be displayed in the drop down menu.'),
      '#size' => 15,
      '#maxlength' => 3,
      '#default_value' => $configuration['ac_max_suggestions'],
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    $form['searchbar_settings']['suggestions']['ac_add_matching_label'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add the matching label to every suggestion in the drop down menu.'),
      '#default_value' => $configuration['ac_add_matching_label'],
    );

    $form['searchbar_settings']['suggestions']['ac_add_context'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add the context (label of the parent concept) to every suggestion in the drop down menu.'),
      '#default_value' => $configuration['ac_add_context'],
    );

    $form['searchbar_settings']['suggestions']['ac_add_facet_name'] = array(
      '#type' => 'checkbox',
      '#title' => t('Additionally add the name of the facet after the context.'),
      '#default_value' => $configuration['ac_add_facet_name'],
      '#states' => array(
        'visible' => array(':input[name="ac_add_context"]' => array('checked' => TRUE)),
      ),
    );

    $form['searchbar_settings']['advanced_settings'] = array(
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#open' => FALSE,
    );

    $form['searchbar_settings']['advanced_settings']['show_facetbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the filter container'),
      '#description' => t('Check it if a container with all selected filters should be displayed beneath the search bar.'),
      '#default_value' => $configuration['show_facetbox'],
    );

    $options = array(
      'concept' => t('Only terms from the suggestions are possible (it searches only in the tagged terms).'),
      'concept free-term' => t('In addition to the suggestions, the entry of free terms is possible (it matches the tagged terms and searches in the content).'),
    );
    $form['searchbar_settings']['advanced_settings']['search_type'] = array(
      '#type' => 'radios',
      '#title' => t('Select the type of search'),
      '#description' => t('The first option minimizes an empty result list. The second option, on the other hand, gives an user the possibility to search in the content.'),
      '#options' => $options,
      '#default_value' => $configuration['search_type'],
    );

    // Tab: Trends settings
    $form['trends_settings'] = array(
      '#type' => 'details',
      '#title' => t('Trends settings'),
      '#group' => 'settings',
    );

    if (PPGraphSearch::isFlotInstalled()) {
      $form['trends_settings']['add_trends'] = array(
        '#type' => 'checkbox',
        '#title' => t('Add Trends chart'),
        '#description' => t('Adds a chart showing the trends for the selected filter of the current search.'),
        '#default_value' => $configuration['add_trends'],
      );

      $form['trends_settings']['trends_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#description' => t('The title above the trends-chart'),
        '#maxlength' => 100,
        '#default_value' => $configuration['trends_title'],
      );

      $form['trends_settings']['trends_description'] = array(
        '#type' => 'textarea',
        '#title' => t('Description'),
        '#description' => t('An additional description between the title and the trends-chart.'),
        '#default_value' => $configuration['trends_description'],
      );

      $form['trends_settings']['trends_chart_type'] = array(
        '#type' => 'select',
        '#title' => t('Calculation'),
        '#description' => t('Select the type for the calculation for the lines.'),
        '#options' =>array(
          'raw_data' => t('Raw data'),
          'simple_moving_average' => t('Simple moving average')
        ),
        '#default_value' => $configuration['trends_chart_type'],
      );

      $form['trends_settings']['trends_colors'] = array(
        '#type' => 'textfield',
        '#title' => t('Colors'),
        '#description' => t('A comma separated list of colors for the lines in the chart (e.g. #00FF00, red, #0000FF).'),
        '#default_value' => $configuration['trends_colors'],
      );
    }
    else {
      // TODO: add a description where to download the module.
      $form['trends_settings']['info'] = array(
        '#markup' => '<div class="messages warning">' . t('To show the trends the "%flot"-module needs to be installed and enabled.', array('%flot' => Link::fromTextAndUrl('flot', Url::fromUri('https://www.drupal.org/project/flot'))->toString())) . '</div>',
      );
    }

    // Tab: Other settings.
    $form['other_settings'] = array(
      '#type' => 'details',
      '#title' => t('Other settings'),
      '#group' => 'settings',
    );

    $form['other_settings']['use_css_file'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use CSS File'),
      '#description' => t('Check if you wish to include the module\'s CSS file.'),
      '#default_value' => $configuration['use_css_file'],
    );

    $form['other_settings']['add_rss_functionality'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add RSS button'),
      '#description' => t('Check if you wish to add the RSS button and its functionallity.'),
      '#default_value' => $configuration['add_rss_functionality'],
    );

    // Tab: Semantic module connection.
    $form['semantic_connection'] = array(
      '#type' => 'details',
      '#title' => t('Semantic module connection'),
      '#group' => 'settings',
      '#tree' => TRUE,
    );

    $form['semantic_connection']['show_in_destinations'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show as concept destination'),
      '#description' => t('Add the content block of this PoolParty GraphSearch configuration to a list of destinations for a concept link if applicable.'),
      '#default_value' => ((isset($configuration['semantic_connection']) && isset($configuration['semantic_connection']['show_in_destinations'])) ? $configuration['semantic_connection']['show_in_destinations'] : TRUE),
    );

    // Add CSS and JS.
    $form['#attached'] = array(
      'library' =>  array(
        'pp_graphsearch/admin_area',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('ac_min_chars') <= 1) {
      $form_state->setErrorByName('ac_min_chars', t('"%field" must be greater than 1.', array('%field' => t('Number of characters'))));
    }
    $year = date('Y');
    if (!empty($form_state->getValue('time_filter_years')) && $form_state->getValue('time_filter_years') >= $year) {
      $form_state->setErrorByName('time_filter_years', t('"%field" must be less than %year.', array('%field' => t('Date filter with years'), '%year' => $year)));
    }
    if ($form_state->getValue('show_similar') && !$form_state->getValue('similar_max_items')) {
      $form_state->setErrorByName('similar_max_items', t('"%field" can not be empty.', array('%field' => t('Number of documents'))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PPGraphSearchConfig $entity */
    $entity = $this->entity;

    $facets_to_show = array();
    $facet_max_items = 10;
    foreach ($form_state->getValue('facets_to_show') as $facet_id => $facet) {
      $weight = $facet['weight'];
      unset($facet['weight']);
      $facet['facet_id'] = $facet_id;
      $facets_to_show[$weight] = $facet;
      if ($facet_max_items < $facet['max_items']) {
        $facet_max_items = $facet['max_items'];
      }
    }
    if (!empty($facets_to_show)) {
      ksort($facets_to_show);
      $facets_to_show = array_values($facets_to_show);
    }

    $components_order = array();
    foreach ($form_state->getValue('components_order') as $component_id => $component) {
      $components_order[$component['weight']] = $component_id;
    }
    if (!empty($components_order)) {
      ksort($components_order);
      $components_order = array_values($components_order);
    }

    $config_values = $form_state->getValues();
    unset($config_values['title']);
    $config_values['facets_to_show'] = $facets_to_show;
    $config_values['facet_max_items'] = $facet_max_items;
    $config_values['components_order'] = $components_order;

    // Update and save the entity.
    $entity->set('title', $form_state->getValue('title'));
    $entity->set('config', $config_values);

    \Drupal::messenger()->addMessage(t('PoolParty GraphSearch configuration %title has been saved.', array('%title' => $form_state->getValue('title'))));
    $entity->save();

    // Clear the cache for this configuration set.
    // @todo: Clear the cache here after the cache implementation is done.
    // $cache_id = 'semantic_connector:sonr_webmining:configuration_set_id:' . $form_state['values']['swid'];
    // cache_clear_all($cache_id, 'cache');
    drupal_flush_all_caches();

    $form_state->setRedirectUrl(Url::fromRoute('entity.pp_graphsearch.collection'));
  }

  public function element_validate_integer_positive($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
      $form_state->setErrorByName($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
    }
  }
}