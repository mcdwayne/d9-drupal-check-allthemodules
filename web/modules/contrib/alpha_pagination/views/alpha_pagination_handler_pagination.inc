<?php

/**
 * @file
 * Definition of alpha_pagination_handler_pagination.
 *
 * @author Michael R. Bagnall (@mbagnall17)
 * @version 1.0
 * @copyright 2015 FlyingFlip Studios, LLC. (@flyingflip)
 * @link http://www.michaelbagnall.com
 * @file alpha_pagination_handler_pagination.inc
 *
 * @license
 * Copyright (c) 2015 FlyingFlip Studios, LLC.
 * This software is open-source licensed under the GNU Public License Version 2 or later
 * The full license is available in the LICENSE.TXT file at the root of this repository
 */

/**
 * Views area handler to display an alphabetic pagination representive of the entire
 * result set for this view and not just the limited number being displayed by the
 * view's configuration
 *
 * @ingroup views_area_handlers
 */
class alpha_pagination_handler_pagination extends views_handler_area {
  /**
   * Our option default definitions.
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['pre_letter_path'] = array(
      'default' => 'by-first-letter',
      'translatable' => FALSE,
    );
    $options['paginate_view_field'] = array(
      'default' => 'title',
      'translatable' => TRUE,
    );

    // Classes.
    $options['paginate_class'] = array(
      'default' => 'alpha-pagination',
      'translatable' => FALSE,
    );
    $options['paginate_list_class'] = array(
      'default' => 'alpha-pagination-list',
      'translatable' => FALSE,
    );
    $options['paginate_active_class'] = array(
      'default' => 'active',
      'translatable' => FALSE,
    );
    $options['paginate_inactive_class'] = array(
      'default' => 'inactive',
      'translatable' => FALSE,
    );
    $options['paginate_link_class'] = array(
      'default' => '',
      'translatable' => FALSE,
    );

    // All.
    $options['paginate_all_display'] = array(
      'default' => 1,
      'translatable' => FALSE,
    );
    $options['paginate_all_class'] = array(
      'default' => 'all',
      'translatable' => FALSE,
    );
    $options['paginate_all_label'] = array(
      'default' => t('All'),
      'translatable' => TRUE,
    );
    $options['paginate_all_position'] = array(
      'default' => 'after',
      'translatable' => FALSE,
    );
    $options['paginate_toggle_empty'] = array(
      'default' => 1,
      'translatable' => FALSE,
    );

    // Numeric.
    $options['paginate_view_numbers'] = array(
      'default' => 0,
      'translatable' => FALSE,
    );
    $options['paginate_numeric_position'] = array(
      'default' => 'before',
      'translatable' => FALSE,
    );
    $options['paginate_numeric_class'] = array(
      'default' => 'numeric',
      'translatable' => FALSE,
    );

    return $options;
  }

  /**
   * We need to clear our cache when changing options or things go badly. It does not
   * pick up on the items with the modified terms because it uses cached items until
   * a cache-clear is performed. This eliminates that step.
   */
  function options_submit(&$form, &$form_state) {
    cache_clear_all($this->getCid(), 'cache', TRUE);
  }

  /**
   * Our options form.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['pre_letter_path'] = array(
      '#title' => t('Path to results view'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $this->options['pre_letter_path'],
      '#description' => t('This is the path to the view that handles the search by letter. No beginning or ending slashes.'),
    );

    if ($this->view->base_table == 'taxonomy_term_data') {
      // Get an array list of all non-image, non-entity or other assorted reference fields.
      $fields = array('name' => 'name');
    }
    else {
      // Get an array list of all non-image, non-entity or other assorted reference fields.
      $fields = array('title' => 'title');  
    }

    $compound_field_types = array('name');
    $single_field_types = array('text', 'text_long', 'text_with_summary');
    $all_field_types = array_merge($single_field_types, $compound_field_types);
    foreach (field_info_field_map() as $field_name => $field_definition) {
      if (in_array($field_definition['type'], $all_field_types)) {
        if (in_array($field_definition['type'], $compound_field_types)) {
          $field_info = field_info_field($field_name);
          foreach (array_keys($field_info['columns']) as $compoundFieldKey) {
            $compound_field_field_name = sprintf('%s:%s', $field_name, $compoundFieldKey);
            $fields[$compound_field_field_name] = $compound_field_field_name;
          }
        } else {
          $fields[$field_name] = $field_name;
        }
      }
    }
    $form['paginate_view_field'] = array(
      '#title' => t('View field to paginate against'),
      '#type' => 'select',
      '#options' => $fields,
      '#default_value' => $this->options['paginate_view_field'],
      '#description' => t('This will be the content field that drives the pagination.'),
    );
    $form['paginate_toggle_empty'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show options without results'),
      '#default_value' => $this->options['paginate_toggle_empty'],
      '#description' => t('Show or hide letters without results'),
    );

    // Class options.
    $form['paginate_classes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Classes'),
      '#description' => t('Provide additional CSS classes on elements in the pagination; separated by spaces.'),
      '#collapsible' => TRUE,
    );
    $form['paginate_class'] = array(
      '#title' => t('Wrapper'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_class'],
      '#description' => t('The wrapper around the item list.'),
      '#fieldset' => 'paginate_classes',
    );
    $form['paginate_list_class'] = array(
      '#title' => t('Item List'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_list_class'],
      '#description' => t('The item list.'),
      '#fieldset' => 'paginate_classes',
    );
    $form['paginate_active_class'] = array(
      '#title' => t('Active item'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_active_class'],
      '#description' => t('The active list item.'),
      '#fieldset' => 'paginate_classes',
    );
    $form['paginate_inactive_class'] = array(
      '#title' => t('Inactive item'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_inactive_class'],
      '#description' => t('The inactive list item(s) that are not links, a.k.a. "no results".'),
      '#fieldset' => 'paginate_classes',
    );
    $form['paginate_link_class'] = array(
      '#title' => t('Link'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_link_class'],
      '#description' => t('The link element.'),
      '#fieldset' => 'paginate_classes',
    );

    // "All" options.
    $form['paginate_all_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('"All" item'),
      '#collapsible' => TRUE,
    );
    $form['paginate_all_display'] = array(
      '#type' => 'select',
      '#title' => t('Display the "All" item'),
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#default_value' => $this->options['paginate_all_display'],
      '#description' => t('Displays the "All" link in the pagination.'),
      '#fieldset' => 'paginate_all_options',
    );
    $form['paginate_all_position'] = array(
      '#type' => 'select',
      '#title' => t('Position'),
      '#options' => array(
        'before' => t('Before'),
        'after' => t('After'),
      ),
      '#default_value' => $this->options['paginate_all_position'],
      '#description' => t('Determines where the "All" item will show up in the pagination.'),
      '#dependency' => array('edit-options-paginate-all-display' => array(1)),
      '#fieldset' => 'paginate_all_options',
    );
    $form['paginate_all_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->options['paginate_all_label'],
      '#description' => t('The label to use for display the "All" item in the pagination.'),
      '#dependency' => array('edit-options-paginate-all-display' => array(1)),
      '#fieldset' => 'paginate_all_options',
    );
    $form['paginate_all_class'] = array(
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_all_class'],
      '#description' => t('CSS classes for "All" item (on <code>&lt;li&gt;</code> element); separated by spaces.'),
      '#dependency' => array('edit-options-paginate-all-display' => array(1)),
      '#fieldset' => 'paginate_all_options',
    );

    // "Numeric" options.
    $form['paginate_numeric_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Numeric items'),
      '#collapsible' => TRUE,
    );
    $form['paginate_view_numbers'] = array(
      '#title' => t('Display numeric items'),
      '#type' => 'select',
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#default_value' => $this->options['paginate_view_numbers'],
      '#description' => t('Displays numeric (0-9) links in the pagination.'),
      '#fieldset' => 'paginate_numeric_options',
    );
    $form['paginate_numeric_position'] = array(
      '#type' => 'select',
      '#title' => t('Position'),
      '#options' => array(
        'before' => t('Before'),
        'after' => t('After'),
      ),
      '#default_value' => $this->options['paginate_numeric_position'],
      '#description' => t('Determines whether numeric items are shown before or after alphabetical links in the pagination.'),
      '#dependency' => array('edit-options-paginate-view-numbers' => array(1)),
      '#fieldset' => 'paginate_numeric_options',
    );
    $form['paginate_numeric_class'] = array(
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->options['paginate_numeric_class'],
      '#description' => t('CSS classes for numeric item (on <code>&lt;li&gt;</code> element); separated by spaces.'),
      '#dependency' => array('edit-options-paginate-view-numbers' => array(1)),
      '#fieldset' => 'paginate_numeric_options',
    );
  }
  /**
   * {@inheritdoc}
   */
  function post_execute(&$values) {
    $this->ensureQuery();
  }

  /**
   * Extract the SQL query from the query information.
   *
   * Once extracted, place it into the options array so it is passed to the render
   * function. This code was lifted nearly verbatim from the views module where the
   * query is constructed for the ui to show the query in the administrative area.
   * I am open to suggestions on how to make this better.
   */
  function ensureQuery() {
    if (empty($this->options['query']) && !empty($this->view->build_info['query'])) {
      $query = $this->view->build_info['query'];
      $quoted = $query->getArguments();
      $connection = Database::getConnection();
      foreach ($quoted as $key => $val) {
        if (is_array($val)) {
          $quoted[$key] = implode(', ', array_map(array(
            $connection,
            'quote'
          ), $val));
        }
        else {
          $quoted[$key] = $connection->quote($val);
        }
      }
      $this->options['query'] = check_plain(strtr($query, $quoted));
    }
  }

  /**
   * Render the alphabetic paginator
   *
   * @param bool $empty
   *   If this area should be emptym then return it as empty.
   *
   * @return string $paginator
   *   A string representing the complete paginator including linked and unlinked options.
   */
  function render($empty = FALSE) {
    // Create the wrapper.
    $wrapper = array(
      '#theme_wrappers' => array('container__alpha_pagination__wrapper'),
      '#attributes' => array(),
      '#attached' => array(
        'library' => array(
          array('alpha_pagination', 'alpha_pagination'),
        )
      ),
    );
    $this->addOptionClasses('paginate_class', $wrapper['#attributes']);

    // Account for an argument-less call to the page.
    if (empty($this->view->args)) {
      $this->view->args[0] = 'all';
    }

    $all_label = !empty($this->options['paginate_all_label']) ? t(check_plain($this->options['paginate_all_label'])) : t('All');

    // Iterate over the alphabet and populate the items for the item list.
    $items = array();
    foreach ($this->getItems() as $key => $is_link) {
      $all = drupal_strtolower($key) === 'all';
      $numeric = is_numeric($key);
      $active = (string) $key === (string) $this->view->args[0];
      $is_link = !$active && ($all || $is_link);
      $label = $all ? $all_label : drupal_ucfirst($key);

      // Theme link item.
      if ($is_link) {
        $item_data = array(
        '#theme' => 'link__alpha_pagination__' . drupal_html_class($key),
        '#text' => $label,
        '#path' => sprintf('%s/%s', $this->options['pre_letter_path'], $key),
        '#options' => array(
          'attributes' => array(),
          'html' => FALSE,
          'query' => drupal_get_query_parameters(),
          ),
        );
        $this->addOptionClasses('paginate_link_class', $item_data['#options']['attributes']);
      }
      // Theme non-link item (a.k.a "inactive").
      //
      // Some themes require text that's in a "pagination" list to be wrapped
      // in another element. Typically, this would be a link, like above,
      // however since this is an inactive option, it should be wrapped in a
      // themeable element that can be targeted by preprocessors if needed.
      elseif ($this->options['paginate_toggle_empty'] || $all || $active) {
        $item_data = array(
          '#type' => 'html_tag',
          '#theme' => 'html_tag__alpha_pagination__inactive',
          '#tag' => 'span',
          '#value' => $label,
        );
      }

      if (!empty($item_data)) {
        // Unfortunately, the implementation of item_list in D7 core does not
        // allow render arrays to be passed as data and requires premature
        // rendering here.
        // @todo In D8, pass the render array directly since it can process it.
        $item = array('data' => drupal_render($item_data));

        // Add the necessary classes for item.
        if ($all) {
          $this->addOptionClasses('paginate_all_class', $item);
        }
        if ($numeric) {
          $this->addOptionClasses('paginate_numeric_class', $item);
        }
        if ($active) {
          $this->addOptionClasses('paginate_active_class', $item);
        }
        elseif (!$is_link) {
          $this->addOptionClasses('paginate_inactive_class', $item);
        }

        // Add the constructed item to the list.
        $items[] = $item;
      }
    }

    // Sanitize any classes provided for the item list.
    $item_list = array(
      '#theme' => 'item_list__alpha_pagination',
      '#attributes' => array(),
      '#items' => $items,
    );
    $this->addOptionClasses('paginate_list_class', $item_list['#attributes']);

    // Append the item list to the wrapper.
    $wrapper[] = $item_list;

    return drupal_render($wrapper);
  }

 /**
  * Add classes to an attributes array from a view option.
  *
  * @param string $option
  *   The name of the view option that contains a space separated list of
  *   classes.
  * @param array $attributes
  *   An attributes array to add the classes to, passed by reference.
  *
  * @return array
  *   An array of classes to be used in a render array.
  */
 protected function addOptionClasses($option, array &$attributes) {
   // Sanitize any classes provided for the item list.
   $classes = array_filter(explode(' ', $this->options[$option]));
   foreach ($classes as &$class) {
     $class = views_clean_css_identifier($class);
   }

   // Don't add any classes if it's empty, which will add an empty attribute.
   if ($classes) {
     if (!isset($attributes['class'])) {
       $attributes['class'] = array();
     }
     $attributes['class'] = array_unique(array_merge($attributes['class'], $classes));
   }

   return $classes;
 }

 /**
  * Retrieves a cache identifier for the view, display and query, if set.
  *
  * @return string
  *   A cache identifier.
  */
 protected function getCid() {
   $this->ensureQuery();
   $query = !empty($this->options['query']) ? md5($this->options['query']) : '';
   return "alpha_pagination:{$this->view->name}:{$this->view->current_display}:$query";
 }

  /**
   * Retrieves the items used to populate the pagination item list.
   *
   * @return array
   *   An associative array containing the result state as the value, keyed by
   *   the letter, number or "all".
   */
  protected function getItems() {
    // Bring in our global language variable.
    global $language;

    // Check to see if this query is cached. If it is, then just pull our
    // results set from it so that things can move quickly through here. We're
    // caching in the event the view has a very large result set.
    $cid = $this->getCid();
    if (($cache = cache_get($cid)) && !empty($cache->data)) {
      return $cache->data;
    }

    // Construct the alphabet pagination items.
    $items = range('A', 'Z');

    // Add arabic alphabet when website's language is in arabic
    if ($language->language == 'ar') {
      $items = array('ا','ب','ت','ث','ج','ح','خ','د','ذ','ر','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ك','ل','م','ن','و','ه','ي');
    }

    // Append or prepend numeric items.
    if (!empty($this->options['paginate_view_numbers'])) {
      if ($this->options['paginate_numeric_position'] === 'after') {
        $items =  array_merge($items, range('0', '9'));
      }
      else {
        $items =  array_merge(range('0', '9'), $items);
      }
    }

    // Append or prepend the "all" item.
    if (!empty($this->options['paginate_all_display'])) {
      if ($this->options['paginate_all_position'] === 'before') {
        $items = array_merge(array('all'), $items);
      }
      else {
        $items[] =  'all';
      }
    }

    // Initialize the results with FALSE values so each prefix is disabled by
    // default. They're later filled in as TRUE below when there is actual
    // entity prefixes that exist.
    $results = array_fill_keys($items, FALSE);

    // Retrieve the entity prefixes.
    if ($prefixes = $this->getEntityPrefixes()) {
      // Ensure that "all" is TRUE if there are prefixes.
      if (isset($results['all'])) {
        $results['all'] = TRUE;
      }

      // Set prefixes to TRUE if it exists from the default list that was
      // constructed from view options above.
      foreach ($prefixes as $prefix) {
        if (isset($results[$prefix])) {
          $results[$prefix] = TRUE;
        }
      }
    }

    // Cache the results.
    cache_set($cid, $results, 'cache');
    return $results;
  }

  /**
   * Retrieve the distinct first character prefix from the field tables.
   *
   * Mark them as TRUE so their pagination item is represented properly.
   *
   * Note that the node title is a special case that we have to take from the
   * node table as opposed to the body or any custom fields.
   *
   * @todo This should be cleaned up more and fixed "properly".
   *
   * @return array
   *   An indexed array containing a unique array of entity prefixes.
   */
  protected function getEntityPrefixes() {
    $prefixes = array();

    if ($entity_ids = $this->getEntityIds()) {
      switch ($this->options['paginate_view_field']) {
        case 'name':
          $table = $this->view->base_table;
          $where = $this->view->base_field;

          // Extract the "name" field from the entity property info.
          $table_data = views_fetch_data($table);
          $entity_info = entity_get_property_info($table_data['table']['entity type']);
          $field = isset($entity_info['properties']['name']['schema field']) ? $entity_info['properties']['name']['schema field'] : 'name';
          break;

        case 'title':
          $table = $this->view->base_table;
          $where = $this->view->base_field;

          // Extract the "title" field from the entity property info.
          $table_data = views_fetch_data($table);
          $entity_info = entity_get_property_info($table_data['table']['entity type']);
          $field = isset($entity_info['properties']['title']['schema field']) ? $entity_info['properties']['title']['schema field'] : 'title';
          break;

        default:
          if (strpos($this->options['paginate_view_field'], ':') === FALSE) {
            // Format field name and table for single value fields
            $field = $this->options['paginate_view_field'] . '_value';
            $table = 'field_data_' . $this->options['paginate_view_field'];
          } else {
            // Format field name and table for compound value fields
            $field = str_replace(':', '_', $this->options['paginate_view_field']);
            $field_name_components = explode(':', $this->options['paginate_view_field']);
            $table = 'field_data_' . $field_name_components[0];
          }
          $where = 'entity_id';
          break;
      }
      $result = db_query('SELECT DISTINCT(SUBSTR(' . $field . ', 1, 1)) AS prefix
                          FROM {' . $table . '}
                          WHERE ' . $where . ' IN ( :nids )', array(':nids' => $entity_ids));
      while ($data = $result->fetchObject()) {
        $prefixes[] = is_numeric($data->prefix) ? $data->prefix : drupal_strtoupper($data->prefix);
      }
    }
    return array_unique(array_filter($prefixes));
  }

  /**
   * Construct the actual SQL query for the view being generated.
   *
   * Then parse it to short-circuit certain conditions that may exist and
   * make any alterations. This is not the most elegant of solutions, but it
   * is very effective.
   *
   * @return array
   *   An indexed array of entity identifiers.
   */
  protected function getEntityIds () {
    $this->ensureQuery();
    $query_parts = explode("\n", $this->options['query']);

    // Get the base field. This will change depending on the type of view we
    // are putting the paginator on.
    $base_field = $this->view->base_field;

    // If we are dealing with a substring, then short circuit it as we are most
    // likely dealing with a glossary contextual filter.
    foreach ($query_parts as $k => $part) {
      if ($position = strpos($part, "SUBSTRING")) {
        $part = substr($part, 0, $position) . " 1 OR " . substr($part, $position);
        $query_parts[$k] = $part;
      }
    }

    // Evaluate the last line looking for anything which may limit the result
    // set as we need results against the entire set of data and not just what
    // is configured in the view.
    $last_line = array_pop($query_parts);
    if (substr($last_line, 0, 5) != "LIMIT") {
      $query_parts[] = $last_line;
    }

    // Construct the query from the array and change the single quotes from
    // HTML special characters back into single quotes.
    $query = join("\n", $query_parts);
    $query = str_replace("&#039;", '\'', $query);
    $query = str_replace("&amp;", '&', $query);
    $query = str_replace("&lt;", '<', $query);
    $query = str_replace("&gt;", '>', $query);

    // Based on our query, get the list of entity identifiers that are affected.
    // These will be used to generate the pagination items.
    $entity_ids = array();
    $result = db_query($query);
    while($data = $result->fetchObject()) {
      $entity_ids[] = $data->$base_field;
    }
    return $entity_ids;
  } 
}
