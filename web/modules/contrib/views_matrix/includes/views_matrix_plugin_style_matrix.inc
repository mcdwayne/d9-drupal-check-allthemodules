<?php
/**
 * @file
 *   Class definition for views matrix plugin.
 */

/**
 * @class
 *   Views Plugin Class
 */
class views_matrix_plugin_style_matrix extends views_plugin_style {

  /**
   * The row weights for click sorting.
   *
   * Maps y field values to their respective "weights" (i.e., values according
   * to which the sorting should be performed).
   *
   * Set externally to get this configuration into
   * views_matrix_plugin_style_matrix::compareRows().
   *
   * @var array
   */
  public $sort_weights = array();

  /**
   * Whether row (click) sorting should be done ascending or descending.
   *
   * Set externally to get this configuration into
   * views_matrix_plugin_style_matrix::compareRows().
   *
   * @var bool
   */
  public $sort_asc;

  /**
   * The rendered fields, after they were rendered initially.
   *
   * @var array|null
   */
  public $rendered_fields;

  public function option_definition() {
    $options = parent::option_definition();

    $options['sticky'] = array('default' => FALSE, 'bool' => TRUE);
    $options['xconfig'] = array('contains' => array(
      'field' => array('default' => ''),
      'sort_field' => array('default' => ''),
      'sort' => array('default' => ''),
      'first_column' => array('default' => ''),
      'class' => array('default' => ''),
    ));
    $options['yconfig'] = array('contains' => array(
      'field' => array('default' => ''),
      'row_numbers' => array('default' => FALSE, 'bool' => TRUE),
      'row_number_format' => array('default' => '[counter]) [label]'),
      'sort_field' => array('default' => ''),
      'sort' => array('default' => ''),
      'class' => array('default' => ''),
    ));
    $options['sortable_headers'] = array('default' => FALSE, 'bool' => TRUE);
    $options['header_init_sort'] = array('default' => 'asc');
    $options['hide_empty_columns'] = array('default' => FALSE, 'bool' => TRUE);
    $options['hide_empty_rows'] = array('default' => FALSE, 'bool' => TRUE);
    $options['one_result_per_cell'] = array('default' => FALSE, 'bool' => TRUE);
    $options['field_positions'] = array('default' => array());

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['sticky'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Drupal style "sticky" table headers (Javascript)'),
      '#default_value' => !empty($this->options['sticky']),
      '#description' => t('The sticky header function only applies to column headers.'),
    );

    $field_options = $this->display->handler->get_field_labels(TRUE);
    $sort_field_options = array(
      '' => t('This field'),
    ) + $field_options;

    $form['xconfig'] = array(
      '#type' => 'fieldset',
      '#title' => t('Column header'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );

    $form['xconfig']['field'] = array(
      '#type' => 'select',
      '#title' => t('Field'),
      '#description' => t('Select the field to display in the column header.'),
      '#options' => $field_options,
      '#default_value' => $this->options['xconfig']['field'],
    );

    $form['xconfig']['sort_field'] = array(
      '#type' => 'select',
      '#title' => t('Sort by'),
      '#description' => t('Select the field to sort by. Picking a different field here will only be meaningful if the values correspond to the field used for the header such as sorting by a Taxonomy term or Entity reference relationship field.'),
      '#options' => $sort_field_options,
      '#default_value' => $this->options['xconfig']['sort_field'],
    );

    $form['xconfig']['sort'] = array(
      '#type' => 'select',
      '#title' => t('Sort direction'),
      '#title_display' => 'invisible',
      '#description' => t('Select the sort direction.'),
      '#options' => array('asc' => t('Ascending'), 'dsc' => t('Descending')),
      '#default_value' => $this->options['xconfig']['sort'],
    );

    $form['xconfig']['first_column'] = array(
      '#type' => 'textfield',
      '#title' => t('Header for first column'),
      '#description' => t('Optionally, specify a header for the first column (containing the column headers).'),
      '#default_value' => $this->options['xconfig']['first_column'],
    );

    $form['xconfig']['class'] = array(
      '#type' => 'textfield',
      '#title' => t('CSS class'),
      '#description' => t('You may use token substitutions from the rewriting section in this class.'),
      '#default_value' => $this->options['xconfig']['class']
    );

    $form['yconfig'] = array(
      '#type' => 'fieldset',
      '#title' => t('Row header'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );

    $form['yconfig']['field'] = array(
      '#type' => 'select',
      '#title' => t('Field'),
      '#description' => t('Select the field to display in the row header.'),
      '#options' => $field_options,
      '#default_value' => $this->options['yconfig']['field'],
    );

    $form['yconfig']['row_numbers'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add row numbers'),
      '#description' => t('Add row numbers to the output for the row header field.'),
      '#default_value' => $this->options['yconfig']['row_numbers'],
    );

    $form['yconfig']['row_number_format'] = array(
      '#type' => 'textfield',
      '#title' => t('Row number format'),
      '#description' => t('Set the complete text to be displayed for the row header. Use "[counter]" as a token for the row number, and "[label]" as the output generated from the field.'),
      '#default_value' => $this->options['yconfig']['row_number_format'],
      '#required' => TRUE,
      '#dependency' => array(
        'edit-style-options-yconfig-row-numbers' => array(1),
      ),
    );

    $form['yconfig']['sort_field'] = array(
      '#type' => 'select',
      '#title' => t('Sort by'),
      '#description' => t('Select the field to sort by. Picking a different field here will only be meaningful if the values correspond to the field used for the header such as sorting by a Taxonomy term or Entity reference relationship field.'),
      '#options' => $sort_field_options,
      '#default_value' => $this->options['yconfig']['sort_field'],
    );

    $form['yconfig']['sort'] = array(
      '#type' => 'select',
      '#title' => t('Sort direction'),
      '#title_display' => 'invisible',
      '#description' => t('Select the sort direction.'),
      '#options' => array('asc' => t('Ascending'), 'dsc' => t('Descending')),
      '#default_value' => $this->options['yconfig']['sort'],
    );

    $form['yconfig']['class'] = array(
      '#type' => 'textfield',
      '#title' => t('CSS class'),
      '#description' => t('You may use token substitutions from the rewriting section in this class.'),
      '#default_value' => $this->options['yconfig']['class']
    );

    $form['sortable_headers'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make column headers sort links'),
      '#description' => t('Emulates the same behavior for the column headers as in an ordinary table. Only makes sense if there will be exactly one result and field value per cell.'),
      '#default_value' => !empty($this->options['sortable_headers']),
    );

    $form['header_init_sort'] = array(
      '#type' => 'select',
      '#title' => t('Inital sorting direction'),
      '#description' => t('Direction of the initial sort when a header is clicked for the first time.'),
      '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
      '#default_value' => isset($this->options['header_init_sort']) ? $this->options['header_init_sort'] : 'asc',
      '#dependency' => array(
        'edit-style-options-sortable-headers' => array(1),
      ),
    );

    $form['hide_empty_columns'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide empty columns'),
      '#description' => t('A column will be empty when "hide with empty" is checked for fields that are displayed and there are no results with data.'),
      '#default_value' => $this->options['hide_empty_columns'],
    );

    $form['hide_empty_rows'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide empty rows'),
      '#description' => t('A row will be empty when "hide with empty" is checked for fields that are displayed and there are no results with data.'),
      '#default_value' => $this->options['hide_empty_rows'],
    );

    $form['one_result_per_cell'] = array(
      '#type' => 'checkbox',
      '#title' => t('Limit to one result per table cell'),
      '#description' => t('If enabled, only the first Views result row encountered will be added to each table cell. Subsequent results with the same values for the column and row header fields will be ignored.'),
      '#default_value' => $this->options['one_result_per_cell'],
    );

    $form['field_positions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Field position'),
      '#description' => t('For each field, choose where to display it.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );

    foreach ($field_options as $field_name => $field_label) {
      $form['field_positions'][$field_name] = array(
        '#type' => 'select',
        '#title' => $field_label,
        '#default_value' => !empty($this->options['field_positions'][$field_name]) ? $this->options['field_positions'][$field_name] : '',
        '#options' => array(
          '' => t('Inside the matrix'),
          'left' => t('On the left of the matrix'),
          'right' => t('On the right of the matrix'),
        ),
      );
    }
  }

  /**
   * Validate row and colum header configuration set. This is only used during
   * runtime. parent::validate() cannot be used because it breaks the Views
   * UI.
   */
  public function canRender() {

    $errors = parent::validate();

    if (empty($this->options['xconfig']['field']) || empty($this->options['yconfig']['field'])) {
      // Do not save without x field or y field.
      $errors[] = t('Style @style requires an x and y field to be defined.', array('@style' => $this->definition['title']));
    }
    else {
      // Check if fields exist still.
      $options = $this->display->handler->get_field_labels(TRUE);

      if (!isset($options[$this->options['xconfig']['field']]) || !isset($options[$this->options['yconfig']['field']])) {
        $errors[] = t('An x or y field is defined, but not does not exist.');
      }
    }

    return $errors;
  }

  /**
   * Return the class of the field headers.
   */
  function headerClasses($dimension, $row_index = NULL) {
    $field_handler = @$this->view->field[$this->options["{$dimension}config"]['field']];
    if (empty($field_handler)) return array();
    $classes = explode(' ', $this->options["{$dimension}config"]['class']);
    foreach ($classes as &$class) {
      $class = $field_handler->tokenize_value($class, $row_index);
      $class = views_clean_css_identifier($class);
    }
    return $classes !== FALSE ? $classes : array();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    // To avoid problems with the Views output cache, we need to incorporate our
    // own (dynamic) sorting information into the query.
    if ($this->options['sortable_headers'] && !empty($_GET['order'])) {
      $sort = $_GET['order'] . ',';
      if (!isset($_GET['sort'])) {
        $sort .= $this->options['header_init_sort'];
      }
      else {
        $sort .= $_GET['sort'];
      }

      $query = $this->view->query;
      if ($query instanceof views_plugin_query_default) {
        $args = array(
          ':empty' => '',
          ':views_matrix_sort' => $sort,
        );
        $query->add_where_expression(0, ':empty <> :views_matrix_sort', $args);
      }
      elseif ($query instanceof SearchApiViewsQuery) {
        $query->setOption('views_matrix_sort', $sort);
      }
    }
  }

  /**
   * Compares two matrix rows, according to the active click sort.
   *
   * @param mixed $a
   *   The first row's y value.
   * @param mixed $b
   *   The second row's y value.
   *
   * @return int
   *   An integer less than, equal to or greater than 0, if $a should be ordered
   *   before, equally to or after $b, respectively.
   */
  public function compareRows($a, $b) {
    if (isset($this->sort_weights[$a]) && is_string($this->sort_weights[$a])) {
      $a_value = "{$this->sort_weights[$a]}";
    }
    else {
      $a_value = "";
    }

    if (isset($this->sort_weights[$b]) && is_string($this->sort_weights[$b])) {
      $b_value = "{$this->sort_weights[$b]}";
    }
    else {
      $b_value = "";
    }

    $ret = strnatcmp($a_value, $b_value);
    return $this->sort_asc ? $ret : -$ret;
  }

  /**
   * Overrides views_plugin_style::render_fields().
   *
   * Avoids rendering the column and row header fields more than once for each
   * value to improve performance.
   */
  function render_fields($result) {
    if (!$this->uses_fields()) {
      return NULL;
    }

    if (!isset($this->rendered_fields)) {
      $this->rendered_fields = array();

      // Get the column and row header field IDs and create an array of cached
      // render values for them.
      $x_key = $this->options['xconfig']['field'];
      $y_key = $this->options['yconfig']['field'];
      $cached_rendered_values = array();

      // If all fields have a field::access FALSE there might be no fields, so
      // there would be no reason to execute this code.
      if (!empty($this->view->field)) {
        foreach ($result as $count => $row) {
          $this->view->row_index = $count;
          /** @var views_handler_field $handler */
          foreach ($this->view->field as $id => $handler) {
            // In case this is one of the header fields, check and fill the
            // cached rendered values.
            if ($id === $x_key || $id === $y_key) {
              // Get the field value for this result, which serves as the cache
              // key for the rendered value.
              $value = $handler->get_value($row);
              // In case of Field API fields, the value might be an array. To
              // still be able to use it as a cache key, we serialize it.
              if (!is_scalar($value)) {
                $value = serialize($value);
              }

              // If we have no cached rendered value yet, compute it.
              if (!isset($cached_rendered_values[$id][$value])) {
                $cached_rendered_values[$id][$value] = $handler->theme($row);
              }
              $rendered = $cached_rendered_values[$id][$value];
            }
            else {
              $rendered = $handler->theme($row);
            }
            $this->rendered_fields[$count][$id] = $rendered;
          }

          $this->row_tokens[$count] = $handler->get_render_tokens(array());
        }
      }
      unset($this->view->row_index);
    }

    return $this->rendered_fields;
  }
}
