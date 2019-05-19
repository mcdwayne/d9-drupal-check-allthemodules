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
 *
 */

function vbsso_listener_report_error($message) {
    if (vbsso_get_platform_config_property(SHAREDAPI_PLATFORM_DRUPAL, VBSSO_CONFIG_PROPERTY_LOG, false)) {
        watchdog(VBSSO_PRODUCT_ID, $message);
    }

    return array(SHAREDAPI_EVENT_FIELD_ERROR_CODE => $message, SHAREDAPI_EVENT_FIELD_ERROR_MESSAGE => $message, SHAREDAPI_EVENT_FIELD_ERROR_DATA => '');
}

/**
 * User Load from json
 *
 * @param string $json from master platform
 * @param integer $create_user flag to create new user
 *
 * @return array|bool
 */
function vbsso_listener_user_load($json, $create_user = 0) {
    $user_by_email = user_load_by_mail($json[SHAREDAPI_EVENT_FIELD_EMAIL]);
    $user_by_username = user_load_by_name($json[SHAREDAPI_EVENT_FIELD_USERNAME]);

    if ($user_by_email === false && $user_by_username === false && $create_user) {
        $new_roles = explode(',', $json[SHAREDAPI_EVENT_FIELD_USERGROUPS]);
        $roles = array();
        if ($vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, NULL))) {
            foreach ($new_roles as $new_role) {
                $roles[$vbsso_usergroups_assoc->$new_role] = $vbsso_usergroups_assoc->$new_role;
            }
        }
        $user_by_email = user_save(
            new stdClass(), array(
                'mail' => $json[SHAREDAPI_EVENT_FIELD_EMAIL],
                'init' => $json[SHAREDAPI_EVENT_FIELD_EMAIL],
                'name' => $json[SHAREDAPI_EVENT_FIELD_USERNAME],
                'status' => 1,
                'roles' => ($roles) ? $roles : array(2 => 2)
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

    if ($rebuild) {
        menu_rebuild();
    }

    return array('data' => array(SHAREDAPI_EVENT_FIELD_VERIFY => true));
}

function vbsso_listener_authentication($json) {
    global $user; // object exists for both guest and authenticated user always.

    if (!isset($user->mail) OR (isset($user->mail) AND $user->mail != $json[SHAREDAPI_EVENT_FIELD_EMAIL])) {
        $u = vbsso_listener_user_load($json, true);
        if (!sharedapi_is_error_data_item($u)) {
            if ($user->uid != $u->uid) {
                vbsso_listener_logout($json);

                $timeout = 60 * $json[SHAREDAPI_EVENT_FIELD_TIMEOUT];
                if ($timeout) {
                    ini_set('session.gc_maxlifetime', $timeout);
                    ini_set('session.cookie_lifetime', $timeout);
                }

                $user = $u;
                user_login_finalize();
            }
        } else {
            return array('error' => $u);
        }
    }
}

function vbsso_listener_logout($json) {
    if (user_is_logged_in()) {
        user_logout();
    }
}

function vbsso_listener_register($json) {
    $u = vbsso_listener_user_load($json, true);

    if (sharedapi_is_error_data_item($u)) {
        return array('error' => $u);
    }
}

/**
 * Change user profile fields callback function
 *
 * @param string $json from Master platform
 *
 * @return array
 */
function vbsso_listener_credentials($json) {
    $vbssoUser = vbsso_listener_user_load($json, false);

    if (sharedapi_is_error_data_item($vbssoUser)) {
        return array('error' => $vbssoUser);
    }
    
    $edit = array();

    if (isset($json[SHAREDAPI_EVENT_FIELD_EMAIL2])) {
        $edit['mail'] = $json[SHAREDAPI_EVENT_FIELD_EMAIL2];
    }

    if (isset($json[SHAREDAPI_EVENT_FIELD_USERNAME2])) {
        $edit['name'] = $json[SHAREDAPI_EVENT_FIELD_USERNAME2];
    }

    if (isset($json[SHAREDAPI_EVENT_FIELD_USERGROUPS2])) {
        $new_roles = explode(',', $json[SHAREDAPI_EVENT_FIELD_USERGROUPS2]);
        if ($vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, null))) {
            foreach ($new_roles as $new_role) {
                $edit['roles'][$vbsso_usergroups_assoc->$new_role] = $vbsso_usergroups_assoc->$new_role;
            }
        }
    }

    if (count($edit)) {
        $ret = user_save($vbssoUser, $edit);
        if ($ret === false) {
            return array('error' => vbsso_listener_report_error('Unable to update user credentials: ' . join(', ', $edit)));
        }
    }
}

/**
 * Override system link for login action
 *
 * @return void
 */
function vbsso_listener_link_login() {
    $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
    sharedapi_url_redirect($url);
}

/**
 * Override system link for logout action
 *
 * @return void
 */
function vbsso_listener_link_logout() {
    $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
    sharedapi_url_redirect($url);
}

/**
 * Override system link for register action
 *
 * @return void
 */
function vbsso_listener_link_register() {
    $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_REGISTER_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
    sharedapi_url_redirect($url);
}

/**
 * Override system link for lost password action
 *
 * @return void
 */
function vbsso_listener_link_lost_password() {
    $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
    sharedapi_url_redirect($url);
}

/**
 * Override links in edit profile form
 *
 * @param object $form edit profile form links override
 *
 * @return void
 */
function vbsso_listener_link_edit_profile(&$form) {
    global $user;
    $user_id = isset($form['#user']->uid) ? $form['#user']->uid : '';
    $user_email = isset($form['#user']->mail) ? $form['#user']->mail : '';

    if ($user->uid == $user_id and variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, null)) {
        sharedapi_url_redirect(variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, null));
    }

    if ($user->uid != $user_id and variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN, null)) {
        sharedapi_url_redirect(variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, null) . md5(trim($user_email)));
    }

}

/**
 * Enter point callback
 *
 * @return void
 */
function vbsso_listener() {
    module_load_include('inc', 'user', 'user.pages');
    $vbsso_drupal_plugin_info = drupal_parse_info_file(drupal_get_path('module', VBSSO_PRODUCT_ID . '7') . '/' . VBSSO_PRODUCT_ID . '7.info');

    sharedapi_data_handler(
        SHAREDAPI_PLATFORM_DRUPAL, VERSION, $vbsso_drupal_plugin_info['version'],
        variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY),
        array(
            SHAREDAPI_EVENT_VERIFY => 'vbsso_listener_verify',
            SHAREDAPI_EVENT_LOGIN => 'vbsso_listener_register',
            SHAREDAPI_EVENT_AUTHENTICATION => 'vbsso_listener_authentication',
            SHAREDAPI_EVENT_LOGOUT => 'vbsso_listener_logout',
            SHAREDAPI_EVENT_REGISTER => 'vbsso_listener_register',
            SHAREDAPI_EVENT_CREDENTIALS => 'vbsso_listener_credentials',
        )
    );
}
