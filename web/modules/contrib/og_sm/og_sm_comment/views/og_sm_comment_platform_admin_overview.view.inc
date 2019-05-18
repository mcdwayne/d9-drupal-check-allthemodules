<?php

/**
 * @file
 * Default view for Platform Comment administration.
 */

$view = new view();
$view->name = 'og_sm_comment_platform_admin_overview';
$view->description = '';
$view->tag = 'default';
$view->base_table = 'comment';
$view->human_name = 'Administer Platform comments';
$view->core = 7;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Master */
$handler = $view->new_display('default', 'Master', 'default');
$handler->display->display_options['title'] = 'Administer comments';
$handler->display->display_options['use_more_always'] = FALSE;
$handler->display->display_options['access']['type'] = 'perm';
$handler->display->display_options['access']['perm'] = 'access platform overview pages';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['exposed_form']['options']['reset_button'] = TRUE;
$handler->display->display_options['exposed_form']['options']['sort_asc_label'] = 'Ascending';
$handler->display->display_options['exposed_form']['options']['sort_desc_label'] = 'Descending';
$handler->display->display_options['pager']['type'] = 'full';
$handler->display->display_options['pager']['options']['items_per_page'] = '50';
$handler->display->display_options['pager']['options']['offset'] = '0';
$handler->display->display_options['pager']['options']['id'] = '0';
$handler->display->display_options['pager']['options']['quantity'] = '9';
$handler->display->display_options['pager']['options']['expose']['offset_label'] = 'Start';
$handler->display->display_options['style_plugin'] = 'table';
$handler->display->display_options['style_options']['columns'] = array(
  'views_bulk_operations' => 'views_bulk_operations',
  'path' => 'path',
  'cid' => 'cid',
  'title' => 'title',
  'title_1' => 'title_1',
  'comment_body' => 'comment_body',
  'name' => 'name',
  'created' => 'created',
  'edit_comment' => 'edit_comment',
  'delete_comment' => 'edit_comment',
);
$handler->display->display_options['style_options']['default'] = '-1';
$handler->display->display_options['style_options']['info'] = array(
  'views_bulk_operations' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'path' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'cid' => array(
    'sortable' => 0,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
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
  'comment_body' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'name' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'created' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'edit_comment' => array(
    'align' => '',
    'separator' => '&emsp;',
    'empty_column' => 0,
  ),
  'delete_comment' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
);
$handler->display->display_options['style_options']['sticky'] = TRUE;
/* Header: Global: Result summary */
$handler->display->display_options['header']['result']['id'] = 'result';
$handler->display->display_options['header']['result']['table'] = 'views';
$handler->display->display_options['header']['result']['field'] = 'result';
$handler->display->display_options['header']['result']['content'] = 'Show @start - @end of @total';
/* Footer: Global: Result summary */
$handler->display->display_options['footer']['result']['id'] = 'result';
$handler->display->display_options['footer']['result']['table'] = 'views';
$handler->display->display_options['footer']['result']['field'] = 'result';
$handler->display->display_options['footer']['result']['content'] = 'Show @start - @end of @total';
/* No results behavior: Global: Text area */
$handler->display->display_options['empty']['area']['id'] = 'area';
$handler->display->display_options['empty']['area']['table'] = 'views';
$handler->display->display_options['empty']['area']['field'] = 'area';
$handler->display->display_options['empty']['area']['empty'] = TRUE;
$handler->display->display_options['empty']['area']['content'] = 'There are no comments in any of your Sites.';
$handler->display->display_options['empty']['area']['format'] = 'plain_text';
/* Relationship: Comment: Content */
$handler->display->display_options['relationships']['nid']['id'] = 'nid';
$handler->display->display_options['relationships']['nid']['table'] = 'comment';
$handler->display->display_options['relationships']['nid']['field'] = 'nid';
$handler->display->display_options['relationships']['nid']['required'] = TRUE;
/* Relationship: Comment: Author */
$handler->display->display_options['relationships']['uid']['id'] = 'uid';
$handler->display->display_options['relationships']['uid']['table'] = 'comment';
$handler->display->display_options['relationships']['uid']['field'] = 'uid';
$handler->display->display_options['relationships']['uid']['required'] = TRUE;
/* Relationship: OG membership: OG membership from Node */
$handler->display->display_options['relationships']['og_membership_rel']['id'] = 'og_membership_rel';
$handler->display->display_options['relationships']['og_membership_rel']['table'] = 'node';
$handler->display->display_options['relationships']['og_membership_rel']['field'] = 'og_membership_rel';
$handler->display->display_options['relationships']['og_membership_rel']['relationship'] = 'nid';
$handler->display->display_options['relationships']['og_membership_rel']['label'] = 'OG membership of node';
$handler->display->display_options['relationships']['og_membership_rel']['required'] = TRUE;
/* Relationship: Entity Reference: Referenced Entity */
$handler->display->display_options['relationships']['og_group_ref_target_id']['id'] = 'og_group_ref_target_id';
$handler->display->display_options['relationships']['og_group_ref_target_id']['table'] = 'og_membership';
$handler->display->display_options['relationships']['og_group_ref_target_id']['field'] = 'og_group_ref_target_id';
$handler->display->display_options['relationships']['og_group_ref_target_id']['relationship'] = 'og_membership_rel';
/* Field: Bulk operations: Comment */
$handler->display->display_options['fields']['views_bulk_operations']['id'] = 'views_bulk_operations';
$handler->display->display_options['fields']['views_bulk_operations']['table'] = 'comment';
$handler->display->display_options['fields']['views_bulk_operations']['field'] = 'views_bulk_operations';
$handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['display_type'] = '0';
$handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['enable_select_all_pages'] = 1;
$handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['row_clickable'] = 1;
$handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['force_single'] = 0;
$handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['entity_load_capacity'] = '10';
$handler->display->display_options['fields']['views_bulk_operations']['vbo_operations'] = array(
  'action::views_bulk_operations_delete_item' => array(
    'selected' => 1,
    'postpone_processing' => 0,
    'skip_confirmation' => 0,
    'override_label' => 0,
    'label' => '',
  ),
);
/* Field: Content: Path */
$handler->display->display_options['fields']['path']['id'] = 'path';
$handler->display->display_options['fields']['path']['table'] = 'node';
$handler->display->display_options['fields']['path']['field'] = 'path';
$handler->display->display_options['fields']['path']['relationship'] = 'nid';
$handler->display->display_options['fields']['path']['exclude'] = TRUE;
$handler->display->display_options['fields']['path']['absolute'] = TRUE;
/* Field: Comment: ID */
$handler->display->display_options['fields']['cid']['id'] = 'cid';
$handler->display->display_options['fields']['cid']['table'] = 'comment';
$handler->display->display_options['fields']['cid']['field'] = 'cid';
$handler->display->display_options['fields']['cid']['exclude'] = TRUE;
$handler->display->display_options['fields']['cid']['link_to_comment'] = FALSE;
/* Field: Content: Title */
$handler->display->display_options['fields']['title']['id'] = 'title';
$handler->display->display_options['fields']['title']['table'] = 'node';
$handler->display->display_options['fields']['title']['field'] = 'title';
$handler->display->display_options['fields']['title']['relationship'] = 'nid';
$handler->display->display_options['fields']['title']['alter']['make_link'] = TRUE;
$handler->display->display_options['fields']['title']['alter']['path'] = '[path]#comment-[cid]';
$handler->display->display_options['fields']['title']['link_to_node'] = FALSE;
/* Field: Content: Title */
$handler->display->display_options['fields']['title_1']['id'] = 'title_1';
$handler->display->display_options['fields']['title_1']['table'] = 'node';
$handler->display->display_options['fields']['title_1']['field'] = 'title';
$handler->display->display_options['fields']['title_1']['relationship'] = 'og_group_ref_target_id';
$handler->display->display_options['fields']['title_1']['label'] = 'Site';
$handler->display->display_options['fields']['title_1']['link_to_node'] = FALSE;
/* Field: Comment: Comment */
$handler->display->display_options['fields']['comment_body']['id'] = 'comment_body';
$handler->display->display_options['fields']['comment_body']['table'] = 'field_data_comment_body';
$handler->display->display_options['fields']['comment_body']['field'] = 'comment_body';
$handler->display->display_options['fields']['comment_body']['label'] = 'Reaction';
$handler->display->display_options['fields']['comment_body']['type'] = 'text_trimmed';
$handler->display->display_options['fields']['comment_body']['settings'] = array(
  'trim_length' => '200',
);
/* Field: User: Name */
$handler->display->display_options['fields']['name']['id'] = 'name';
$handler->display->display_options['fields']['name']['table'] = 'users';
$handler->display->display_options['fields']['name']['field'] = 'name';
$handler->display->display_options['fields']['name']['relationship'] = 'uid';
$handler->display->display_options['fields']['name']['label'] = 'Author';
$handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
/* Field: Comment: Post date */
$handler->display->display_options['fields']['created']['id'] = 'created';
$handler->display->display_options['fields']['created']['table'] = 'comment';
$handler->display->display_options['fields']['created']['field'] = 'created';
$handler->display->display_options['fields']['created']['label'] = 'Created';
$handler->display->display_options['fields']['created']['date_format'] = 'short';
$handler->display->display_options['fields']['created']['second_date_format'] = 'short';
/* Field: Comment: Edit link */
$handler->display->display_options['fields']['edit_comment']['id'] = 'edit_comment';
$handler->display->display_options['fields']['edit_comment']['table'] = 'comment';
$handler->display->display_options['fields']['edit_comment']['field'] = 'edit_comment';
$handler->display->display_options['fields']['edit_comment']['label'] = 'Actions';
$handler->display->display_options['fields']['edit_comment']['text'] = 'edit';
/* Field: Comment: Delete link */
$handler->display->display_options['fields']['delete_comment']['id'] = 'delete_comment';
$handler->display->display_options['fields']['delete_comment']['table'] = 'comment';
$handler->display->display_options['fields']['delete_comment']['field'] = 'delete_comment';
$handler->display->display_options['fields']['delete_comment']['label'] = '';
$handler->display->display_options['fields']['delete_comment']['element_label_colon'] = FALSE;
$handler->display->display_options['fields']['delete_comment']['text'] = 'remove';
/* Sort criterion: Comment: Post date */
$handler->display->display_options['sorts']['created']['id'] = 'created';
$handler->display->display_options['sorts']['created']['table'] = 'comment';
$handler->display->display_options['sorts']['created']['field'] = 'created';
$handler->display->display_options['sorts']['created']['order'] = 'DESC';
$handler->display->display_options['sorts']['created']['expose']['label'] = 'Created';
/* Filter criterion: Content: Title */
$handler->display->display_options['filters']['title']['id'] = 'title';
$handler->display->display_options['filters']['title']['table'] = 'node';
$handler->display->display_options['filters']['title']['field'] = 'title';
$handler->display->display_options['filters']['title']['relationship'] = 'nid';
$handler->display->display_options['filters']['title']['operator'] = 'contains';
$handler->display->display_options['filters']['title']['group'] = 1;
$handler->display->display_options['filters']['title']['exposed'] = TRUE;
$handler->display->display_options['filters']['title']['expose']['operator_id'] = 'title_op';
$handler->display->display_options['filters']['title']['expose']['label'] = 'Title content contains';
$handler->display->display_options['filters']['title']['expose']['operator'] = 'title_op';
$handler->display->display_options['filters']['title']['expose']['identifier'] = 'title';
$handler->display->display_options['filters']['title']['expose']['remember_roles'] = array(
  2 => '2',
  1 => 0,
  3 => 0,
  5 => 0,
  4 => 0,
  6 => 0,
);
/* Filter criterion: Comment: Comment (comment_body) */
$handler->display->display_options['filters']['comment_body_value']['id'] = 'comment_body_value';
$handler->display->display_options['filters']['comment_body_value']['table'] = 'field_data_comment_body';
$handler->display->display_options['filters']['comment_body_value']['field'] = 'comment_body_value';
$handler->display->display_options['filters']['comment_body_value']['operator'] = 'contains';
$handler->display->display_options['filters']['comment_body_value']['group'] = 1;
$handler->display->display_options['filters']['comment_body_value']['exposed'] = TRUE;
$handler->display->display_options['filters']['comment_body_value']['expose']['operator_id'] = 'comment_body_value_op';
$handler->display->display_options['filters']['comment_body_value']['expose']['label'] = 'Comment contains';
$handler->display->display_options['filters']['comment_body_value']['expose']['operator'] = 'comment_body_value_op';
$handler->display->display_options['filters']['comment_body_value']['expose']['identifier'] = 'comment_body_value';
$handler->display->display_options['filters']['comment_body_value']['expose']['remember_roles'] = array(
  2 => '2',
  1 => 0,
  3 => 0,
  5 => 0,
  4 => 0,
  6 => 0,
);
/* Filter criterion: OG membership: Site */
$handler->display->display_options['filters']['og_sm_sites']['id'] = 'og_sm_sites';
$handler->display->display_options['filters']['og_sm_sites']['table'] = 'og_membership';
$handler->display->display_options['filters']['og_sm_sites']['field'] = 'og_sm_sites';
$handler->display->display_options['filters']['og_sm_sites']['relationship'] = 'nid';
$handler->display->display_options['filters']['og_sm_sites']['group'] = 1;
$handler->display->display_options['filters']['og_sm_sites']['exposed'] = TRUE;
$handler->display->display_options['filters']['og_sm_sites']['expose']['operator_id'] = 'og_sm_sites_op';
$handler->display->display_options['filters']['og_sm_sites']['expose']['label'] = 'Site';
$handler->display->display_options['filters']['og_sm_sites']['expose']['operator'] = 'og_sm_sites_op';
$handler->display->display_options['filters']['og_sm_sites']['expose']['identifier'] = 'og_sm_sites';
$handler->display->display_options['filters']['og_sm_sites']['expose']['remember_roles'] = array(
  2 => '2',
  1 => 0,
  3 => 0,
  4 => 0,
  5 => 0,
  10 => 0,
  6 => 0,
  9 => 0,
  7 => 0,
);
$handler->display->display_options['filters']['og_sm_sites']['manageable_sites'] = 1;
/* Filter criterion: User: Name */
$handler->display->display_options['filters']['uid']['id'] = 'uid';
$handler->display->display_options['filters']['uid']['table'] = 'users';
$handler->display->display_options['filters']['uid']['field'] = 'uid';
$handler->display->display_options['filters']['uid']['relationship'] = 'uid';
$handler->display->display_options['filters']['uid']['value'] = '';
$handler->display->display_options['filters']['uid']['group'] = 1;
$handler->display->display_options['filters']['uid']['exposed'] = TRUE;
$handler->display->display_options['filters']['uid']['expose']['operator_id'] = 'uid_op';
$handler->display->display_options['filters']['uid']['expose']['label'] = 'Author';
$handler->display->display_options['filters']['uid']['expose']['operator'] = 'uid_op';
$handler->display->display_options['filters']['uid']['expose']['identifier'] = 'uid';
$handler->display->display_options['filters']['uid']['expose']['remember_roles'] = array(
  2 => '2',
  1 => 0,
  3 => 0,
  5 => 0,
  4 => 0,
  6 => 0,
);
/* Filter criterion: OG membership: Manageable sites by current user */
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['id'] = 'og_sm_manageable_sites_by_current_user';
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['table'] = 'og_membership';
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['field'] = 'og_sm_manageable_sites_by_current_user';
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['relationship'] = 'nid';
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['value'] = '1';
$handler->display->display_options['filters']['og_sm_manageable_sites_by_current_user']['group'] = 1;

/* Display: Overview */
$handler = $view->new_display('page', 'Overview', 'overview');
$handler->display->display_options['path'] = 'admin/content/comment/platform';
$handler->display->display_options['menu']['type'] = 'tab';
$handler->display->display_options['menu']['title'] = 'Platform comments';
$handler->display->display_options['menu']['weight'] = '0';
$handler->display->display_options['menu']['name'] = 'management';
$handler->display->display_options['menu']['context'] = 0;
$handler->display->display_options['menu']['context_only_inline'] = 0;

$translatables['og_sm_comment_platform_admin_overview'] = array(
  t('Master'),
  t('Administer comments'),
  t('more'),
  t('Apply'),
  t('Reset'),
  t('Sort by'),
  t('Ascending'),
  t('Descending'),
  t('Items per page'),
  t('- All -'),
  t('Start'),
  t('« first'),
  t('‹ previous'),
  t('next ›'),
  t('last »'),
  t('Show @start - @end of @total'),
  t('There are no comments in any of your Sites.'),
  t('Content'),
  t('author'),
  t('OG membership of node'),
  t('Content entity referenced from og_group_ref'),
  t('Comment'),
  t('- Choose an operation -'),
  t('Path'),
  t('ID'),
  t('Title'),
  t('Site'),
  t('Reaction'),
  t('Author'),
  t('Created'),
  t('Actions'),
  t('edit'),
  t('remove'),
  t('Title content contains'),
  t('Comment contains'),
  t('Overview'),
);
