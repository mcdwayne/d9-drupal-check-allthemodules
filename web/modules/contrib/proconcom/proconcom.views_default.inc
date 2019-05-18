<?php

function proconcom_views_default_views() {
$view = new view;
$view->name = 'proconcom_arguments';
$view->description = 'List arguments of a parent node';
$view->tag = '';
$view->base_table = 'node';
$view->human_name = 'proconcom arguments';
$view->core = 0;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Defaults */
$handler = $view->new_display('default', 'Defaults', 'default');
$handler->display->display_options['title'] = 'Arguments';
$handler->display->display_options['access']['type'] = 'none';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['pager']['type'] = 'some';
$handler->display->display_options['pager']['options']['items_per_page'] = '99';
$handler->display->display_options['style_plugin'] = 'default';
$handler->display->display_options['row_plugin'] = 'node';
$handler->display->display_options['row_options']['build_mode'] = 'full';

/* No results behavior: Global: Text area */
$handler->display->display_options['empty']['area']['id'] = 'area';
$handler->display->display_options['empty']['area']['table'] = 'views';
$handler->display->display_options['empty']['area']['field'] = 'area';
$handler->display->display_options['empty']['area']['label'] = 'No content found';
$handler->display->display_options['empty']['area']['empty'] = TRUE;
$handler->display->display_options['empty']['area']['content'] = '<p> No arguments has been created yet.</p>';
$handler->display->display_options['empty']['area']['format'] = 'filtered_html';

/* Sort criterion: proconcom: Argument type */
$handler->display->display_options['sorts']['proconcom_type_value']['id'] = 'proconcom_type_value';
$handler->display->display_options['sorts']['proconcom_type_value']['table'] = 'field_data_proconcom_type';
$handler->display->display_options['sorts']['proconcom_type_value']['field'] = 'proconcom_type_value';

/* Argument: proconcom: Parent Node ID */
$handler->display->display_options['arguments']['parent_nid']['id'] = 'parent_nid';
$handler->display->display_options['arguments']['parent_nid']['table'] = 'proconcom';
$handler->display->display_options['arguments']['parent_nid']['field'] = 'parent_nid';
$handler->display->display_options['arguments']['parent_nid']['default_action'] = 'default';
$handler->display->display_options['arguments']['parent_nid']['default_argument_type'] = 'fixed';
$handler->display->display_options['arguments']['parent_nid']['summary']['number_of_records'] = '0';
$handler->display->display_options['arguments']['parent_nid']['summary']['format'] = 'default_summary';
$handler->display->display_options['arguments']['parent_nid']['summary_options']['items_per_page'] = '25';

/* Filter: Node: Type */
$handler->display->display_options['filters']['type']['id'] = 'type';
$handler->display->display_options['filters']['type']['table'] = 'node';
$handler->display->display_options['filters']['type']['field'] = 'type';
$handler->display->display_options['filters']['type']['value'] = array(
  0 => 'proconcom_argument',
);

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page_1');
$handler->display->display_options['path'] = 'proconcom_list_arguments/%';
	
$views[$view->name] = $view;

  return $views;
}