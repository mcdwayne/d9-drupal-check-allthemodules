

function maestro_if_task_toggle(obj) {
  if(obj.value != undefined) {
    if(obj.value == 'byvariable') {
      jQuery('#byvar').attr('open', 'open');
      jQuery('#bystatus').removeAttr('open');
    }
    else {
      jQuery('#bystatus').attr('open', 'open');
      jQuery('#byvar').removeAttr('open');
    }
  }
}

/**
 * Used in the maestro interactive task assignment details
 * @param val
 */
function maestro_task_editor_assignments_assignto(val) {
  if(val == 'user') {  //hide role
    jQuery('.maestro-engine-assignments-hidden-role').css('display', 'none');
    jQuery('.maestro-engine-assignments-hidden-role').css('visibility', 'none');
    
    jQuery('.maestro-engine-assignments-hidden-user').css('display', 'block');
    jQuery('.maestro-engine-assignments-hidden-user').css('visibility', 'visible');
  }
  else {  //hide user
    jQuery('.maestro-engine-assignments-hidden-role').css('display', 'block');
    jQuery('.maestro-engine-assignments-hidden-role').css('visibility', 'visible');
    
    jQuery('.maestro-engine-assignments-hidden-user').css('display', 'none');
    jQuery('.maestro-engine-assignments-hidden-user').css('visibility', 'none');
  }
}

/**
 * Used in the maestro interactive task notifications details
 * @param val
 */
function maestro_task_editor_notifications_assignto(val) {
  if(val == 'user') {  //hide role
    jQuery('.maestro-engine-notifications-hidden-role').css('display', 'none');
    jQuery('.maestro-engine-notifications-hidden-role').css('visibility', 'none');
    
    jQuery('.maestro-engine-notifications-hidden-user').css('display', 'block');
    jQuery('.maestro-engine-notifications-hidden-user').css('visibility', 'visible');
  }
  else {  //hide user
    jQuery('.maestro-engine-notifications-hidden-role').css('display', 'block');
    jQuery('.maestro-engine-notifications-hidden-role').css('visibility', 'visible');
    
    jQuery('.maestro-engine-notifications-hidden-user').css('display', 'none');
    jQuery('.maestro-engine-notifications-hidden-user').css('visibility', 'none');
  }
}


/**
 * Used in the maestro interactive task assignment details
 * @param val
 */
function maestro_task_editor_assignments_assignby(val) {
  if(val == 'fixed') {  //hide variable and show role or user
    jQuery('.maestro-engine-assignments-hidden-variable').css('display', 'none');
    jQuery('.maestro-engine-assignments-hidden-variable').css('visibility', 'none');
    
    jQuery('.maestro-engine-user-and-role').css('display', 'block');
    jQuery('.maestro-engine-user-and-role').css('visibility', 'visible');
  }
  else {  //hide user and role and show variable
    jQuery('.maestro-engine-assignments-hidden-variable').css('display', 'block');
    jQuery('.maestro-engine-assignments-hidden-variable').css('visibility', 'visible');
    
    jQuery('.maestro-engine-user-and-role').css('display', 'none');
    jQuery('.maestro-engine-user-and-role').css('visibility', 'none');
  }
}

/**
 * Used in the maestro interactive task notifications details
 * @param val
 */
function maestro_task_editor_notifications_assignby(val) {
  if(val == 'fixed') {  //hide variable and show role or user
    jQuery('.maestro-engine-notifications-hidden-variable').css('display', 'none');
    jQuery('.maestro-engine-notifications-hidden-variable').css('visibility', 'none');
    
    jQuery('.maestro-engine-user-and-role-notifications').css('display', 'block');
    jQuery('.maestro-engine-user-and-role-notifications').css('visibility', 'visible');
  }
  else {  //hide user and role and show variable
    jQuery('.maestro-engine-notifications-hidden-variable').css('display', 'block');
    jQuery('.maestro-engine-notifications-hidden-variable').css('visibility', 'visible');
    
    jQuery('.maestro-engine-user-and-role-notifications').css('display', 'none');
    jQuery('.maestro-engine-user-and-role-notifications').css('visibility', 'none');
  }
}

