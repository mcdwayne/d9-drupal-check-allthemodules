<?php
/**
 * @file
 * Defines default views for the PM Expense module.
 */

/**
 * Implements hook_views_default_views().
 */
function pmexpense_views_default_views() {
  $views = array();

  $view = new view();
  $view->name = 'pmexpenses_list';
  $view->description = '';
  $view->tag = 'pm';
  $view->base_table = 'node';
  $view->human_name = 'PM Expense';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Expenses';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'Project Management expense: access';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'title' => 'title',
    'title_1' => 'title_1',
    'title_2' => 'title_2',
    'pmexpense_date' => 'pmexpense_date',
    'pm_amount' => 'pm_amount',
    'pm_handler_field_operation' => 'pm_handler_field_operation',
  );
  $handler->display->display_options['style_options']['default'] = 'pmexpense_date';
  $handler->display->display_options['style_options']['info'] = array(
    'title' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'title_1' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'title_2' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'pmexpense_date' => array(
      'sortable' => 1,
      'default_sort_order' => 'desc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'pm_amount' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'pm_handler_field_operation' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  $handler->display->display_options['style_options']['sticky'] = TRUE;
  $handler->display->display_options['style_options']['empty_table'] = TRUE;
  /* Header: Content: PM Add Content Link */
  $handler->display->display_options['header']['pm_handler_area_addcontent']['id'] = 'pm_handler_area_addcontent';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['table'] = 'node';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['field'] = 'pm_handler_area_addcontent';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['node_type'] = 'pmexpense';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['redirect'] = TRUE;
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'No results match your search';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = 'No results match your search.';
  $handler->display->display_options['empty']['area']['format'] = 'plain_text';
  /* Relationship: Drupal PM: PM Organization */
  $handler->display->display_options['relationships']['pmorganization_nid']['id'] = 'pmorganization_nid';
  $handler->display->display_options['relationships']['pmorganization_nid']['table'] = 'pmorganization_index';
  $handler->display->display_options['relationships']['pmorganization_nid']['field'] = 'pmorganization_nid';
  /* Relationship: Drupal PM: PM Project */
  $handler->display->display_options['relationships']['pmproject_nid']['id'] = 'pmproject_nid';
  $handler->display->display_options['relationships']['pmproject_nid']['table'] = 'pmproject_index';
  $handler->display->display_options['relationships']['pmproject_nid']['field'] = 'pmproject_nid';
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title']['id'] = 'title';
  $handler->display->display_options['fields']['title']['table'] = 'node';
  $handler->display->display_options['fields']['title']['field'] = 'title';
  $handler->display->display_options['fields']['title']['label'] = 'Expense';
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = FALSE;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = FALSE;
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title_1']['id'] = 'title_1';
  $handler->display->display_options['fields']['title_1']['table'] = 'node';
  $handler->display->display_options['fields']['title_1']['field'] = 'title';
  $handler->display->display_options['fields']['title_1']['relationship'] = 'pmorganization_nid';
  $handler->display->display_options['fields']['title_1']['label'] = 'Organization';
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title_2']['id'] = 'title_2';
  $handler->display->display_options['fields']['title_2']['table'] = 'node';
  $handler->display->display_options['fields']['title_2']['field'] = 'title';
  $handler->display->display_options['fields']['title_2']['relationship'] = 'pmproject_nid';
  $handler->display->display_options['fields']['title_2']['label'] = 'Project';
  /* Field: Content: Expense Date */
  $handler->display->display_options['fields']['pmexpense_date']['id'] = 'pmexpense_date';
  $handler->display->display_options['fields']['pmexpense_date']['table'] = 'field_data_pmexpense_date';
  $handler->display->display_options['fields']['pmexpense_date']['field'] = 'pmexpense_date';
  $handler->display->display_options['fields']['pmexpense_date']['settings'] = array(
    'format_type' => 'medium',
    'fromto' => 'both',
    'multiple_number' => '',
    'multiple_from' => '',
    'multiple_to' => '',
  );
  /* Field: Content: Amount */
  $handler->display->display_options['fields']['pm_amount']['id'] = 'pm_amount';
  $handler->display->display_options['fields']['pm_amount']['table'] = 'field_data_pm_amount';
  $handler->display->display_options['fields']['pm_amount']['field'] = 'pm_amount';
  $handler->display->display_options['fields']['pm_amount']['element_class'] = 'align-right';
  $handler->display->display_options['fields']['pm_amount']['settings'] = array(
    'thousand_separator' => ' ',
    'decimal_separator' => '.',
    'scale' => '2',
    'prefix_suffix' => 1,
  );
  /* Field: Content: PM Operations */
  $handler->display->display_options['fields']['pm_handler_field_operation']['id'] = 'pm_handler_field_operation';
  $handler->display->display_options['fields']['pm_handler_field_operation']['table'] = 'node';
  $handler->display->display_options['fields']['pm_handler_field_operation']['field'] = 'pm_handler_field_operation';
  $handler->display->display_options['fields']['pm_handler_field_operation']['label'] = 'Operations';
  $handler->display->display_options['fields']['pm_handler_field_operation']['display_add_icon'] = 1;
  $handler->display->display_options['fields']['pm_handler_field_operation']['display_icons'] = 1;
  /* Filter criterion: Content: Title */
  $handler->display->display_options['filters']['title']['id'] = 'title';
  $handler->display->display_options['filters']['title']['table'] = 'node';
  $handler->display->display_options['filters']['title']['field'] = 'title';
  $handler->display->display_options['filters']['title']['operator'] = 'contains';
  $handler->display->display_options['filters']['title']['group'] = 1;
  $handler->display->display_options['filters']['title']['exposed'] = TRUE;
  $handler->display->display_options['filters']['title']['expose']['operator_id'] = 'title_op';
  $handler->display->display_options['filters']['title']['expose']['label'] = 'Title';
  $handler->display->display_options['filters']['title']['expose']['operator'] = 'title_op';
  $handler->display->display_options['filters']['title']['expose']['identifier'] = 'title';
  $handler->display->display_options['filters']['title']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
  );
  /* Filter criterion: Content: Title */
  $handler->display->display_options['filters']['title_1']['id'] = 'title_1';
  $handler->display->display_options['filters']['title_1']['table'] = 'node';
  $handler->display->display_options['filters']['title_1']['field'] = 'title';
  $handler->display->display_options['filters']['title_1']['relationship'] = 'pmorganization_nid';
  $handler->display->display_options['filters']['title_1']['operator'] = 'contains';
  $handler->display->display_options['filters']['title_1']['group'] = 1;
  $handler->display->display_options['filters']['title_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['title_1']['expose']['operator_id'] = 'title_1_op';
  $handler->display->display_options['filters']['title_1']['expose']['label'] = 'Organization';
  $handler->display->display_options['filters']['title_1']['expose']['operator'] = 'title_1_op';
  $handler->display->display_options['filters']['title_1']['expose']['identifier'] = 'title_1';
  $handler->display->display_options['filters']['title_1']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
  );
  /* Filter criterion: Content: Title */
  $handler->display->display_options['filters']['title_2']['id'] = 'title_2';
  $handler->display->display_options['filters']['title_2']['table'] = 'node';
  $handler->display->display_options['filters']['title_2']['field'] = 'title';
  $handler->display->display_options['filters']['title_2']['relationship'] = 'pmproject_nid';
  $handler->display->display_options['filters']['title_2']['operator'] = 'contains';
  $handler->display->display_options['filters']['title_2']['group'] = 1;
  $handler->display->display_options['filters']['title_2']['exposed'] = TRUE;
  $handler->display->display_options['filters']['title_2']['expose']['operator_id'] = 'title_2_op';
  $handler->display->display_options['filters']['title_2']['expose']['label'] = 'Project';
  $handler->display->display_options['filters']['title_2']['expose']['operator'] = 'title_2_op';
  $handler->display->display_options['filters']['title_2']['expose']['identifier'] = 'title_2';
  $handler->display->display_options['filters']['title_2']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
  );
  /* Filter criterion: Content: Published */
  $handler->display->display_options['filters']['status']['id'] = 'status';
  $handler->display->display_options['filters']['status']['table'] = 'node';
  $handler->display->display_options['filters']['status']['field'] = 'status';
  $handler->display->display_options['filters']['status']['value'] = 1;
  $handler->display->display_options['filters']['status']['group'] = 1;
  $handler->display->display_options['filters']['status']['expose']['operator'] = FALSE;
  /* Filter criterion: Content: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'node';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'pmexpense' => 'pmexpense',
  );
  $handler->display->display_options['filters']['type']['group'] = 1;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'pm/expenses';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Expenses';

  $views[$view->name] = $view;

  return $views;
}
