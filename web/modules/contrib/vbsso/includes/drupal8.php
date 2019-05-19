<?php
/**
 * -----------------------------------------------------------------------
 * vBSSO is a solution which helps you connect to different software platforms
 * via secure Single Sign-On.
 *
 * Copyright (c) 2011-2017 vBSSO. All Rights Reserved.
 * This software is the proprietary information of vBSSO.
 *
 * Author URI: http://www.vbsso.com
 * License: GPL version 2 or later -
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 */

/**
 * Log error in drupal
 * 
 * @param string $message message to log
 *
 * @return array
 */
function vbsso_listener_report_error($message) {
    if (vbsso_get_platform_config_property(SHAREDAPI_PLATFORM_DRUPAL, VBSSO_CONFIG_PROPERTY_LOG, false)) {
        \Drupal::logger(VBSSO_PRODUCT_ID)->notice($message);
    }

    return array(SHAREDAPI_EVENT_FIELD_ERROR_CODE => $message, SHAREDAPI_EVENT_FIELD_ERROR_MESSAGE => $message, SHAREDAPI_EVENT_FIELD_ERROR_DATA => '');
}

/**
 * Load User from json
 *
 * @param string $json string from master platform
 * @param int $create_user flag to create new user
 *
 * @return array|bool
 */
function vbsso_listener_user_load($json, $create_user = 0) {
    $user_by_email = user_load_by_mail($json[SHAREDAPI_EVENT_FIELD_EMAIL]);
    $user_by_username = user_load_by_name($json[SHAREDAPI_EVENT_FIELD_USERNAME]);

    if ($user_by_email === false && $user_by_username === false && $create_user) {
        $new_roles = explode(',', $json[SHAREDAPI_EVENT_FIELD_USERGROUPS]);
        $roles = array();
        if ($vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, null))) {
            foreach ($new_roles as $new_role) {
                $roles[] = $vbsso_usergroups_assoc->$new_role;
            }
        }
        $user_by_email = user_save(
            array(
                'mail' => $json[SHAREDAPI_EVENT_FIELD_EMAIL],
                'init' => $json[SHAREDAPI_EVENT_FIELD_EMAIL],
                'name' => $json[SHAREDAPI_EVENT_FIELD_USERNAME],
                'status' => 1,
                'roles' => $roles
            )
        );
    }

    return $user_by_email ? $user_by_email : vbsso_listener_report_error('Unable to load user: ' . $json[SHAREDAPI_EVENT_FIELD_USERNAME] . '/' . $json[SHAREDAPI_EVENT_FIELD_EMAIL]);
}

function vbsso_listener_verify($json) {
    $rebuild = false;

    $supported = vbsso_get_supported_api_properties();
    foreach ($supported as $key => $item) {
        if (variable_get($key, null) != $json[$item['field']]) {
            $rebuild = true;
            variable_set($key, $json[$item['field']]);
        }
    }

//    if ($rebuild) {
//        menu_router_rebuild();
//    }

    return array('data' => array(SHAREDAPI_EVENT_FIELD_VERIFY => true));
}

/**
 * Function to authentication logged/created user
 *
 * @param string $json from Master platform
 *
 * @return array
 */
function vbsso_listener_authentication($json) {
    $user = \Drupal::currentUser();
    if (empty($user->getEmail()) or $user->getEmail() and $user->getEmail() != $json[SHAREDAPI_EVENT_FIELD_EMAIL]) {
        $vbssoUser = vbsso_listener_user_load($json, true);

        if (sharedapi_is_error_data_item($vbssoUser)) {
            return array('error' => $vbssoUser);
        }

        if ($user->id() != $vbssoUser->id()) {
            vbsso_listener_logout();

            $timeout = 60 * $json[SHAREDAPI_EVENT_FIELD_TIMEOUT];
            if ($timeout) {
                ini_set('session.gc_maxlifetime', $timeout);
                ini_set('session.cookie_lifetime', $timeout);
            }

            $user = $vbssoUser;
            user_login_finalize($user);
        }

    }
}

/**
 * Register new user on slave
 *
 * @param string $jsonData from master platform
 *
 * @return array
 */
function vbsso_listener_register($jsonData) {
    $user = vbsso_listener_user_load($jsonData, true);

    if (sharedapi_is_error_data_item($user)) {
        return array('error' => $user);
    }
}

/**
 * Logout user from Slave platform
 *
 * @return void
 */
function vbsso_listener_logout() {
    if (\Drupal::currentUser()->isAuthenticated()) {
        user_logout();
    }
}



/**
 * Change user profile fields callback function
 *
 * @param string $jsonData from Master platform
 *
 * @return array
 */
function vbsso_listener_credentials($jsonData) {
    $vbUser = vbsso_listener_user_load($jsonData, false);
    
    /* Return error */
    if (sharedapi_is_error_data_item($vbUser)) {
        return array('error' => $vbUser);
    }

    $changed = array();
    
    /* Email field in profile */
    if (isset($jsonData[SHAREDAPI_EVENT_FIELD_EMAIL2])) {
        $changed['mail'] = $jsonData[SHAREDAPI_EVENT_FIELD_EMAIL2];
    }

    /* User Name field in profile */
    if (isset($jsonData[SHAREDAPI_EVENT_FIELD_USERNAME2])) {
        $changed['name'] = $jsonData[SHAREDAPI_EVENT_FIELD_USERNAME2];
    }

    /* Changed user group */
    if (isset($jsonData[SHAREDAPI_EVENT_FIELD_USERGROUPS2])) {
        $new_roles = explode(',', $jsonData[SHAREDAPI_EVENT_FIELD_USERGROUPS2]);
        if ($vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, null))) {
            foreach ($new_roles as $new_role) {
                $changed['roles'][] = $vbsso_usergroups_assoc->$new_role;
            }
        }
    }

    if (count($changed)) {
        $ret = user_update($vbUser, $changed);
        if ($ret === false) {
            return array('error' => vbsso_listener_report_error('Unable to update user credentials: ' . join(', ', $changed)));
        }
    }
}

//function vbsso_listener_link_login() {
//    $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
//    sharedapi_url_redirect($url);
//}

//function vbsso_listener_link_edit_profile(&$form) {
//    global $user;
//    if (strcmp(VERSION, '7.0') >= 0)  {
//        $user_id = isset($form['#user']->uid) ? $form['#user']->uid : '';
//        $user_email = isset($form['#user']->mail) ? $form['#user']->mail : '';
//    } else {
//        $user_id = isset($form['#uid']) ? $form['#uid'] : '';
//        $user_email = isset($form['_account']['#value']->mail) ? $form['_account']['#value']->mail : '';
//    };
//
//    if ($user->uid == $user_id) {
//        if (variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, null))
//            sharedapi_url_redirect(variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, null));
//    } else {
//        if (variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN, null))
//            sharedapi_url_redirect(variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, null) . md5(trim($user_email)));
//    }
//}

/**
 * Get User groups from vBulletin
 *
 * @return array|mixed
 */
function vbsso_get_vb_usergroups() {
    $baa_username = sharedapi_decode_data(
        variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY), variable_get(VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME, null)
    );

    $baa_password = sharedapi_decode_data(
        variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY), variable_get(VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD, null)
    );

    $vbug = sharedapi_post(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL, ''), false, $baa_username, $baa_password);
    return ($vbug['error_string']) ? $vbug : json_decode($vbug['response']);
}
