<?php
/**
 * @file
 * Default views for the Project Management Issue module.
 */

/**
 * Implements hook_views_default_views().
 */
function pmissue_views_default_views() {
  $view = new view();
  $view->name = 'pmissue_list';
  $view->description = '';
  $view->tag = 'pm';
  $view->base_table = 'node';
  $view->human_name = 'PM Issue';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Issues';
  $handler->display->display_options['use_more'] = TRUE;
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['link_display'] = 'custom_url';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'Project Management Issue: access';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'title' => 'title',
    'title_1' => 'title_1',
    'title_2' => 'title_2',
    'pm_status' => 'pm_status',
    'pm_priority' => 'pm_priority',
    'pm_category' => 'pm_category',
    'pm_assigned' => 'pm_assigned',
    'edit_node' => 'edit_node',
    'delete_node' => 'edit_node',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
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
      'empty_column' => 1,
    ),
    'title_2' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
    'pm_status' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
    'pm_priority' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
    'pm_category' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
    'pm_assigned' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
    'edit_node' => array(
      'align' => '',
      'separator' => ' ',
      'empty_column' => 1,
    ),
    'delete_node' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 1,
    ),
  );
  $handler->display->display_options['style_options']['sticky'] = TRUE;
  $handler->display->display_options['style_options']['empty_table'] = TRUE;
  /* Header: Content: PM Add Content Link */
  $handler->display->display_options['header']['pm_handler_area_addcontent']['id'] = 'pm_handler_area_addcontent';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['table'] = 'node';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['field'] = 'pm_handler_area_addcontent';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['node_type'] = 'pmissue';
  $handler->display->display_options['header']['pm_handler_area_addcontent']['redirect'] = TRUE;
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
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
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = FALSE;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = FALSE;
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title_1']['id'] = 'title_1';
  $handler->display->display_options['fields']['title_1']['table'] = 'node';
  $handler->display->display_options['fields']['title_1']['field'] = 'title';
  $handler->display->display_options['fields']['title_1']['relationship'] = 'pmproject_nid';
  $handler->display->display_options['fields']['title_1']['label'] = 'Project';
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title_2']['id'] = 'title_2';
  $handler->display->display_options['fields']['title_2']['table'] = 'node';
  $handler->display->display_options['fields']['title_2']['field'] = 'title';
  $handler->display->display_options['fields']['title_2']['relationship'] = 'pmorganization_nid';
  $handler->display->display_options['fields']['title_2']['label'] = 'Organization';
  /* Field: Content: Status */
  $handler->display->display_options['fields']['pm_status']['id'] = 'pm_status';
  $handler->display->display_options['fields']['pm_status']['table'] = 'field_data_pm_status';
  $handler->display->display_options['fields']['pm_status']['field'] = 'pm_status';
  $handler->display->display_options['fields']['pm_status']['type'] = 'pm_list_default';
  /* Field: Content: Priority */
  $handler->display->display_options['fields']['pm_priority']['id'] = 'pm_priority';
  $handler->display->display_options['fields']['pm_priority']['table'] = 'field_data_pm_priority';
  $handler->display->display_options['fields']['pm_priority']['field'] = 'pm_priority';
  $handler->display->display_options['fields']['pm_priority']['type'] = 'pm_list_default';
  /* Field: Content: Category */
  $handler->display->display_options['fields']['pm_category']['id'] = 'pm_category';
  $handler->display->display_options['fields']['pm_category']['table'] = 'field_data_pm_category';
  $handler->display->display_options['fields']['pm_category']['field'] = 'pm_category';
  $handler->display->display_options['fields']['pm_category']['type'] = 'pm_list_default';
  /* Field: Content: Assigned to */
  $handler->display->display_options['fields']['pm_assigned']['id'] = 'pm_assigned';
  $handler->display->display_options['fields']['pm_assigned']['table'] = 'field_data_pm_assigned';
  $handler->display->display_options['fields']['pm_assigned']['field'] = 'pm_assigned';
  $handler->display->display_options['fields']['pm_assigned']['settings'] = array(
    'link' => 1,
  );
  /* Field: Content: PM Operations */
  $handler->display->display_options['fields']['pm_handler_field_operation']['id'] = 'pm_handler_field_operation';
  $handler->display->display_options['fields']['pm_handler_field_operation']['table'] = 'node';
  $handler->display->display_options['fields']['pm_handler_field_operation']['field'] = 'pm_handler_field_operation';
  $handler->display->display_options['fields']['pm_handler_field_operation']['label'] = 'Operations';
  $handler->display->display_options['fields']['pm_handler_field_operation']['display_add_icon'] = 1;
  $handler->display->display_options['fields']['pm_handler_field_operation']['display_icons'] = 1;
  /* Sort criterion: Content: Weight (pm_weight) */
  $handler->display->display_options['sorts']['pm_weight_value']['id'] = 'pm_weight_value';
  $handler->display->display_options['sorts']['pm_weight_value']['table'] = 'field_data_pm_weight';
  $handler->display->display_options['sorts']['pm_weight_value']['field'] = 'pm_weight_value';
  /* Sort criterion: Content: Post date */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'node';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  /* Contextual filter: Content: Nid */
  $handler->display->display_options['arguments']['nid']['id'] = 'nid';
  $handler->display->display_options['arguments']['nid']['table'] = 'node';
  $handler->display->display_options['arguments']['nid']['field'] = 'nid';
  $handler->display->display_options['arguments']['nid']['relationship'] = 'pmproject_nid';
  $handler->display->display_options['arguments']['nid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['nid']['title_enable'] = TRUE;
  $handler->display->display_options['arguments']['nid']['title'] = 'Issues for %1';
  $handler->display->display_options['arguments']['nid']['default_argument_type'] = 'node';
  $handler->display->display_options['arguments']['nid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['nid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['nid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['nid']['specify_validation'] = TRUE;
  $handler->display->display_options['arguments']['nid']['validate']['type'] = 'node';
  $handler->display->display_options['arguments']['nid']['validate_options']['types'] = array(
    'pmproject' => 'pmproject',
  );
  $handler->display->display_options['arguments']['nid']['validate']['fail'] = 'ignore';
  /* Contextual filter: Content: Nid */
  $handler->display->display_options['arguments']['nid_1']['id'] = 'nid_1';
  $handler->display->display_options['arguments']['nid_1']['table'] = 'node';
  $handler->display->display_options['arguments']['nid_1']['field'] = 'nid';
  $handler->display->display_options['arguments']['nid_1']['relationship'] = 'pmorganization_nid';
  $handler->display->display_options['arguments']['nid_1']['default_action'] = 'default';
  $handler->display->display_options['arguments']['nid_1']['title_enable'] = TRUE;
  $handler->display->display_options['arguments']['nid_1']['title'] = ' for %1';
  $handler->display->display_options['arguments']['nid_1']['default_argument_type'] = 'node';
  $handler->display->display_options['arguments']['nid_1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['nid_1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['nid_1']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['nid_1']['specify_validation'] = TRUE;
  $handler->display->display_options['arguments']['nid_1']['validate']['type'] = 'node';
  $handler->display->display_options['arguments']['nid_1']['validate_options']['types'] = array(
    'pmorganization' => 'pmorganization',
  );
  $handler->display->display_options['arguments']['nid_1']['validate']['fail'] = 'ignore';
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
    'pmissue' => 'pmissue',
  );
  $handler->display->display_options['filters']['type']['group'] = 1;
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
    4 => 0,
  );
  /* Filter criterion: Content: Status (pm_status) */
  $handler->display->display_options['filters']['pm_status_value']['id'] = 'pm_status_value';
  $handler->display->display_options['filters']['pm_status_value']['table'] = 'field_data_pm_status';
  $handler->display->display_options['filters']['pm_status_value']['field'] = 'pm_status_value';
  $handler->display->display_options['filters']['pm_status_value']['group'] = 1;
  $handler->display->display_options['filters']['pm_status_value']['exposed'] = TRUE;
  $handler->display->display_options['filters']['pm_status_value']['expose']['operator_id'] = 'pm_status_value_op';
  $handler->display->display_options['filters']['pm_status_value']['expose']['label'] = 'Status';
  $handler->display->display_options['filters']['pm_status_value']['expose']['operator'] = 'pm_status_value_op';
  $handler->display->display_options['filters']['pm_status_value']['expose']['identifier'] = 'pm_status_value';
  $handler->display->display_options['filters']['pm_status_value']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
    4 => 0,
  );
  $handler->display->display_options['filters']['pm_status_value']['reduce_duplicates'] = TRUE;
  /* Filter criterion: Content: Priority (pm_priority) */
  $handler->display->display_options['filters']['pm_priority_value']['id'] = 'pm_priority_value';
  $handler->display->display_options['filters']['pm_priority_value']['table'] = 'field_data_pm_priority';
  $handler->display->display_options['filters']['pm_priority_value']['field'] = 'pm_priority_value';
  $handler->display->display_options['filters']['pm_priority_value']['group'] = 1;
  $handler->display->display_options['filters']['pm_priority_value']['exposed'] = TRUE;
  $handler->display->display_options['filters']['pm_priority_value']['expose']['operator_id'] = 'pm_priority_value_op';
  $handler->display->display_options['filters']['pm_priority_value']['expose']['label'] = 'Priority';
  $handler->display->display_options['filters']['pm_priority_value']['expose']['operator'] = 'pm_priority_value_op';
  $handler->display->display_options['filters']['pm_priority_value']['expose']['identifier'] = 'pm_priority_value';
  $handler->display->display_options['filters']['pm_priority_value']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
    4 => 0,
  );
  $handler->display->display_options['filters']['pm_priority_value']['reduce_duplicates'] = TRUE;
  /* Filter criterion: Content: Category (pm_category) */
  $handler->display->display_options['filters']['pm_category_value']['id'] = 'pm_category_value';
  $handler->display->display_options['filters']['pm_category_value']['table'] = 'field_data_pm_category';
  $handler->display->display_options['filters']['pm_category_value']['field'] = 'pm_category_value';
  $handler->display->display_options['filters']['pm_category_value']['group'] = 1;
  $handler->display->display_options['filters']['pm_category_value']['exposed'] = TRUE;
  $handler->display->display_options['filters']['pm_category_value']['expose']['operator_id'] = 'pm_category_value_op';
  $handler->display->display_options['filters']['pm_category_value']['expose']['label'] = 'Category';
  $handler->display->display_options['filters']['pm_category_value']['expose']['operator'] = 'pm_category_value_op';
  $handler->display->display_options['filters']['pm_category_value']['expose']['identifier'] = 'pm_category_value';
  $handler->display->display_options['filters']['pm_category_value']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
    4 => 0,
  );
  $handler->display->display_options['filters']['pm_category_value']['reduce_duplicates'] = TRUE;
  /* Filter criterion: Content: Title */
  $handler->display->display_options['filters']['title_1']['id'] = 'title_1';
  $handler->display->display_options['filters']['title_1']['table'] = 'node';
  $handler->display->display_options['filters']['title_1']['field'] = 'title';
  $handler->display->display_options['filters']['title_1']['relationship'] = 'pmproject_nid';
  $handler->display->display_options['filters']['title_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['title_1']['expose']['operator_id'] = 'title_1_op';
  $handler->display->display_options['filters']['title_1']['expose']['label'] = 'Project';
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
  $handler->display->display_options['filters']['title_2']['relationship'] = 'pmorganization_nid';
  $handler->display->display_options['filters']['title_2']['exposed'] = TRUE;
  $handler->display->display_options['filters']['title_2']['expose']['operator_id'] = 'title_2_op';
  $handler->display->display_options['filters']['title_2']['expose']['label'] = 'Organization';
  $handler->display->display_options['filters']['title_2']['expose']['operator'] = 'title_2_op';
  $handler->display->display_options['filters']['title_2']['expose']['identifier'] = 'title_2';
  $handler->display->display_options['filters']['title_2']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
  );

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
  $handler->display->display_options['defaults']['use_more'] = FALSE;
  $handler->display->display_options['defaults']['use_more_always'] = FALSE;
  $handler->display->display_options['defaults']['use_more_always'] = FALSE;
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['defaults']['use_more_text'] = FALSE;
  $handler->display->display_options['defaults']['pager'] = FALSE;
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['pager']['options']['id'] = '0';
  $handler->display->display_options['pager']['options']['quantity'] = '9';
  $handler->display->display_options['path'] = 'pm/issues/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'List';
  $handler->display->display_options['menu']['weight'] = '-10';
  $handler->display->display_options['menu']['context'] = 0;
  $handler->display->display_options['menu']['context_only_inline'] = 0;
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Issues';
  $handler->display->display_options['tab_options']['weight'] = '0';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block_1');
  $handler->display->display_options['defaults']['use_more'] = FALSE;
  $handler->display->display_options['use_more'] = TRUE;
  $handler->display->display_options['defaults']['use_more_always'] = FALSE;
  $handler->display->display_options['defaults']['use_more_always'] = FALSE;
  $handler->display->display_options['use_more_always'] = TRUE;
  $handler->display->display_options['defaults']['use_more_text'] = FALSE;
  $handler->display->display_options['link_url'] = 'pm/issues/!1';
  $handler->display->display_options['defaults']['pager'] = FALSE;
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['pager']['options']['items_per_page'] = '5';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['defaults']['style_plugin'] = FALSE;
  $handler->display->display_options['style_plugin'] = 'list';
  $handler->display->display_options['defaults']['style_options'] = FALSE;
  $handler->display->display_options['defaults']['row_plugin'] = FALSE;
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['defaults']['row_options'] = FALSE;
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title']['id'] = 'title';
  $handler->display->display_options['fields']['title']['table'] = 'node';
  $handler->display->display_options['fields']['title']['field'] = 'title';
  $handler->display->display_options['fields']['title']['label'] = '';
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = FALSE;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = FALSE;
  $handler->display->display_options['fields']['title']['element_label_colon'] = FALSE;
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  /* Contextual filter: Content: Nid */
  $handler->display->display_options['arguments']['nid']['id'] = 'nid';
  $handler->display->display_options['arguments']['nid']['table'] = 'node';
  $handler->display->display_options['arguments']['nid']['field'] = 'nid';
  $handler->display->display_options['arguments']['nid']['relationship'] = 'project_nid';
  $handler->display->display_options['arguments']['nid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['nid']['default_argument_type'] = 'node';
  $handler->display->display_options['arguments']['nid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['nid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['nid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['nid']['specify_validation'] = TRUE;
  $handler->display->display_options['arguments']['nid']['validate']['type'] = 'node';
  $handler->display->display_options['arguments']['nid']['validate_options']['types'] = array(
    'pmproject' => 'pmproject',
  );
  $handler->display->display_options['arguments']['nid']['validate']['fail'] = 'ignore';

  $views[$view->name] = $view;

  return $views;
}
