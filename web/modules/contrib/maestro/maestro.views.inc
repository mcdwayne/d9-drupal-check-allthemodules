<?php


/**
 * Implements hook_views_data_alter().
 */
function maestro_views_data_alter(array &$data) {
  $data['maestro_process']['initiator_username'] = array(
    'title' => t('Initiator'),
    'group' => t('Maestro Process'),
    'field' => array(
      'title' => t('The initiator of the process.'),
      'help' => t('Translates the UID into a username for display.'),
      'id' => 'maestro_process_initiator_user',
    ),
  );
  
  $data['maestro_process']['completed'] = array(
    'title' => t('Completed Time'),
    'group' => t('Maestro Process'),
    'field' => array(
      'title' => t('The completed time of the process.'),
      'help' => t('Shows a time if complete or blank if incomplete.'),
      'id' => 'maestro_process_completed_time',
    ),
  );
  
  $data['maestro_process']['template_id_filter'] = array(
    'title' => t('Maestro Template Name'),
    'group' => t('Maestro Process'),
    'filter' => array(
      'title' => t('Filter by Maestro Template Name'),
      'help' => t('Provides a custom drop-down filter for filtering on template names/ids.'),
      'field' => 'template_id',
      'id' => 'maestro_process_template_filter'
    ),
  );
  
  $data['maestro_process']['admin_operations'] = array(
    'title' => t('Admin Operations'),
    'group' => t('Maestro Process'),
    'field' => array(
      'title' => t('Administrative Operations'),
      'help' => t('Provides Process based admin operations.'),
      'id' => 'maestro_admin_operations',
    ),
  );
  
  $data['maestro_process']['process_details'] = array(
    'title' => t('Maestro Process Details'),
    'group' => t('Maestro Process'),
    'field' => array(
      'title' => t('Show the Maestro Process Details'),
      'help' => t('Shows the status bar (if applicable) and any views configured.'),
      'id' => 'maestro_process_process_details'
    ),
  );
  
  $data['maestro_queue']['active_handler'] = array(
    'title' => t('Active Handler'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('The Active Task Handler'),
      'help' => t('Tries to provide a link to the appropriate handler.'),
      'id' => 'maestro_active_handler',
    ),
  );
  
  $data['maestro_queue']['active_assignments'] = array(
    'title' => t('Active Assignments'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('The Active Assignees of this task'),
      'help' => t('Lists those assigned to the current task being listed.'),
      'id' => 'maestro_active_assignments',
    ),
  );
  
  $data['maestro_queue']['admin_operations'] = array(
    'title' => t('Admin Operations'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('Administrative Operations'),
      'help' => t('Provides links to do things such as task reassignment, tracing of processes etc..'),
      'id' => 'maestro_admin_operations',
    ),
  );
  
  $data['maestro_queue']['completion_time'] = array(
    'title' => t('Formatted Completion Time'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('Formatted Completion Time'),
      'help' => t('Provides the completion time in the user\'s default time format'),
      'id' => 'maestro_completed_timestamp',
    ),
  );
  
  $data['maestro_queue']['started_time'] = array(
    'title' => t('Formatted Started Time'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('Formatted Started Time'),
      'help' => t('Provides the started time in the user\'s default time format'),
      'id' => 'maestro_started_timestamp',
    ),
  );
  
  $data['maestro_queue']['who_completed'] = array(
    'title' => t('Who completed the task'),
    'group' => t('Maestro Queue'),
    'field' => array(
      'title' => t('The user who completed the task'),
      'help' => t('Provides a few options to show who completed the task.'),
      'id' => 'maestro_user_who_completed',
    ),
  );
  
  $data['maestro_entity_identifiers']['entity_label'] = array(
    'title' => t('Label of the Entity'),
    'group' => t('Maestro Entity Identifiers'),
    'field' => array(
      'title' => t('Label of the Entity'),
      'help' => t('Provides the label of the entity which is pointed to by the entity identifiers entry.'),
      'id' => 'maestro_entity_identifiers_label',
    ),
  );
  
  $data['maestro_entity_identifiers']['entity_edit_link'] = array(
    'title' => t('Edit Link to Entity'),
    'group' => t('Maestro Entity Identifiers'),
    'field' => array(
      'title' => t('Edit Link to Entity'),
      'help' => t('If the user has the rights to edit, an edit link is generated.'),
      'id' => 'maestro_entity_identifiers_edit_link',
    ),
  );
}