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
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 *
 */

define('VBSSO_DRUPAL_VERSION', substr(VERSION, 0, 1));

require_once(dirname(__FILE__) . '/vendor/com.extremeidea.vbsso/vbsso-connect-shared/vbsso_shared.php');

if (file_exists(dirname(__FILE__) . '/config.custom.php')) {
    require_once(dirname(__FILE__) . '/config.custom.php');
}

require_once(dirname(__FILE__) . '/includes/api.php');

function vbsso_help_hook($path, $arg) {
    switch ($path) {
        case 'admin/help#' . VBSSO_PRODUCT_ID:
        case 'admin/config/people/' . VBSSO_PRODUCT_ID:
            $output = '';
            $output .= '<h3>' . t('About') . '</h3>';
            $output .= '<p>' . t(VBSSO_PLATFORM_DESCRIPTION_HTML) . '</p>';
            return $output;
    }
}

/**
 * Settings module link
 * 
 * @return mixed
 */
function vbsso_menu_hook() {
    $items['admin/config/people/' . VBSSO_PRODUCT_ID . '/settings'] = array(
        'title' => VBSSO_PRODUCT_NAME,
        'description' => t(VBSSO_PLATFORM_DESCRIPTION_HTML),
        'page callback' => 'drupal_get_form',
        'page arguments' => array('vbsso' . VBSSO_DRUPAL_VERSION . '_admin_form'),
        'access arguments' => array('administer site configuration'),
    );

    $items[VBSSO_PRODUCT_ID . '/1.0'] = array(
        'page callback' => 'vbsso_listener',
        'access callback' => TRUE,
        'type' => MENU_CALLBACK,
    );

    return $items;
}

function vbsso_menu_alter_hook(&$items) {
    $override_links = vbsso_get_platform_config_property(SHAREDAPI_PLATFORM_DRUPAL, VBSSO_CONFIG_PROPERTY_OVERRIDE_LINKS, false);

    $v = variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL, null);
    if (!empty($v) && $override_links && variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE, 1)) {
        $items['user/login']['module'] = VBSSO_PRODUCT_ID . VBSSO_DRUPAL_VERSION;
        $items['user/login']['page callback'] = 'vbsso_listener_link_login';
    }

    $logout = 'user/logout';
    $v = variable_get(VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL, null);
    if (!empty($v) && $override_links) {
        $items[$logout]['module'] = VBSSO_PRODUCT_ID . VBSSO_DRUPAL_VERSION;
        $items[$logout]['page callback'] = 'vbsso_listener_link_logout';
        unset($items[$logout]['file']);
    }

    $v = variable_get(VBSSO_NAMED_EVENT_FIELD_REGISTER_URL, null);
    if (!empty($v) && $override_links) {
        $items['user/register']['module'] = VBSSO_PRODUCT_ID . VBSSO_DRUPAL_VERSION;
        $items['user/register']['page callback'] = 'vbsso_listener_link_register';
        if (isset($items['user/register']['file'])) unset($items['user/register']['file']);
    }

    $v = variable_get(VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL, null);
    if (!empty($v) && $override_links) {
        $items['user/password']['module'] = VBSSO_PRODUCT_ID . VBSSO_DRUPAL_VERSION;
        $items['user/password']['page callback'] = 'vbsso_listener_link_lost_password';
        unset($items['user/password']['file']);
    }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function vbsso_form_user_login_block_alter_hook(&$form, &$form_state, $userpage = false) {
    if ($userpage AND variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE, 1))
        vbsso_listener_link_login();

    if (variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL, null)) {
        unset($form['name']);
        unset($form['pass']);
        unset($form['actions']);
        unset($form['submit']);

        $form['#action'] = variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL, null);
        $form['vb_login_username'] = array(
            '#type' => 'textfield',
            '#maxlength' => 128,
            '#size' => ($userpage) ? 60 : 25,
            '#title' => t('Username'),
            '#required' => TRUE,
        );
        $form['vb_login_password'] = array(
            '#type' => 'password',
            '#maxlength' => 64,
            '#size' => ($userpage) ? 60 : 25,
            '#title' => t('Password'),
            '#required' => TRUE,
        );
        $form['cookieuser'] = array(
            '#type' => 'checkbox',
            '#title' => t('Remember me.'),
        );
        $form['do'] = array('#type' => 'hidden', '#value' => 'login');
        $form['submit'] = array('#type' => 'submit', '#value' => t('Login'));

        //moving links to the end of form
        if (isset($form['links'])) {
            $temp = $form['links'];
            unset($form['links']);
            $form['links'] = $temp;
        }
    }
}

/**
 * Form builder; Configure the vbsso system.
 *
 * @ingroup forms
 */
function vbsso_admin_form_hook() {
    global $base_url;
    $form = array();

    $extensions = vbsso_verify_loaded_extensions();
    if (count($extensions)) {
        foreach ($extensions as $ext) {
            drupal_set_message('The following PHP extension are required to be installed: ' . $ext, 'error');
        }
    }

    // Footer Link block
    $form[VBSSO_PRODUCT_ID]['link'] = array(
        '#type' => 'fieldset',
        '#title' => t('Footer Link'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#description' => VBSSO_PLATFORM_FOOTER_LINK_DESCRIPTION_HTML,
    );

    $options = vbsso_get_platform_footer_link_options();
    $form[VBSSO_PRODUCT_ID]['link'][VBSSO_PLATFORM_FOOTER_LINK_PROPERTY] = array(
        '#type' => 'radios',
        '#title' => t('Footer Link'),
        '#default_value' => variable_get(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY, VBSSO_PLATFORM_FOOTER_LINK_SHOW_EVERYWHERE),
        '#options' => $options,
    );


    //Platform block
    $form[VBSSO_PRODUCT_ID]['platform'] = array(
        '#type' => 'fieldset',
        '#title' => t('Platform'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    $form[VBSSO_PRODUCT_ID]['platform'][VBSSO_NAMED_EVENT_FIELD_API_KEY] = array(
        '#type' => 'textfield',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_API_KEY_TITLE),
        '#size' => 80,
        '#maxlength' => 255,
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY),
        '#disabled' => (variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL, null)) ? TRUE : FALSE,
        '#description' => VBSSO_NAMED_EVENT_FIELD_API_KEY_WARNING,
    );

    $form[VBSSO_PRODUCT_ID]['platform'][VBSSO_NAMED_EVENT_FIELD_LISTENER_URL] = array(
        '#type' => 'textfield',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_LISTENER_URL_TITLE),
        '#size' => 80,
        '#maxlength' => 255,
        '#default_value' => $base_url . '/vbsso/1.0',
        '#attributes' => array('readonly' => 'readonly'),
    );


    //Settings block
    $form[VBSSO_PRODUCT_ID]['settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('Settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS, 1),
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE, 1),
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE, 1),
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE, 1),
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, 1),
    );

    $form[VBSSO_PRODUCT_ID]['settings'][VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN] = array(
        '#type' => 'checkbox',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN_TITLE),
        '#default_value' => variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN, 1),
    );


    //Admin roles block
    $form[VBSSO_PRODUCT_ID]['adminroles'] = array(
        '#type' => 'fieldset',
        '#title' => t(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME_TITLE),
        '#description' => 'Roles are used to show native user profile.',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    $roles = user_roles();
    $saved_roles = explode(',', variable_get(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, ''));

    foreach ($roles as $role_id => $role_name) {
        $form[VBSSO_PRODUCT_ID]['adminroles'][VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME . '_' . $role_id] = array(
            '#type' => 'checkbox',
            '#title' => t($role_name),
            '#default_value' => (in_array($role_id, $saved_roles)) ? 1 : 0,
        );
    }


    //Usergroups block
    if (variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL, null)) {
        $form[VBSSO_PRODUCT_ID]['usergroups'] = array(
            '#type' => 'fieldset',
            '#title' => t(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC_TITLE),
            '#description' => 'Associate vBulletin usergroups with Drupal Roles.',
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );

        $vb_usergroups = vbsso_get_vb_usergroups();
        $vbsso_usergroups_assoc = json_decode(variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, ''));

        if (isset($vb_usergroups['error_string'])) {
            $form[VBSSO_PRODUCT_ID]['usergroups'][] = array(
                '#type' => 'item',
                '#title' => 'Error',
                '#markup' => $vb_usergroups['error_string'],
            );
        } else {
            foreach ($vb_usergroups as $vb_usergroup) {
                $gid = $vb_usergroup->usergroupid;
                $form[VBSSO_PRODUCT_ID]['usergroups'][VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC   . '_' . $gid] = array(
                    '#type' => 'select',
                    '#title' => t($vb_usergroup->title),
                    '#default_value' => ($vbsso_usergroups_assoc AND isset($vbsso_usergroups_assoc->$gid)) ? $vbsso_usergroups_assoc->$gid : 2,
                    '#options' => $roles,
                );
            }
        }
    }

    return $form;
}

function vbsso_admin_form_submit_hook($form, &$form_state) {
    if ($form_state['values'][VBSSO_NAMED_EVENT_FIELD_API_KEY])
        variable_set(VBSSO_NAMED_EVENT_FIELD_API_KEY, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_API_KEY]);
    variable_set(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY, $form_state['values'][VBSSO_PLATFORM_FOOTER_LINK_PROPERTY]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE]);
    variable_set(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN, $form_state['values'][VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN]);

    $roles = user_roles();
    $roles_to_save = array();
    foreach ($roles as $role_id => $role_name) {
        if ($form_state['values'][VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME . '_' . $role_id] == 1) {
            $roles_to_save[] = $role_id;
        }
    }
    $roles_to_save = join(',', $roles_to_save);
    variable_set(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, $roles_to_save);

    if (variable_get(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL, null)) {
        $vb_usergroups = vbsso_get_vb_usergroups();
        $vbsso_usergroups_assoc = array();
        foreach ($vb_usergroups as $vb_usergroup) {
            if (isset($_POST[VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC . '_' . $vb_usergroup->usergroupid])) {
                $ug = $_POST[VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC . '_' . $vb_usergroup->usergroupid];
            } else {
                $ug = 2;
            }

            $vbsso_usergroups_assoc[$vb_usergroup->usergroupid] = $ug;

        }

        variable_set(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC, json_encode($vbsso_usergroups_assoc));
    }
}

function vbsso_is_user_admin() {
    global $user;

    $is_admin = false;
    $roles = user_roles();
    $saved_roles_string = variable_get(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, '');
    if (!empty($roles) AND !empty($saved_roles_string)) {
        $saved_roles_ids = explode(',', variable_get(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME, ''));
        $saved_roles_names = array();
        foreach ($saved_roles_ids as $role_id) {
            $saved_roles_names[] = $roles[$role_id];
        }

        foreach ($user->roles as $role) {
            if (in_array($role, $saved_roles_names)) {
                $is_admin = true;
                break;
            }
        }
    }
    return $is_admin;
}

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

function vbsso_uninstall() {
    variable_del(VBSSO_NAMED_EVENT_FIELD_API_KEY);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LISTENER_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS);
    variable_del(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE);
    variable_del(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE);
    variable_del(VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE);
    variable_del(VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN);
    variable_del(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN);
    variable_del(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC);
    variable_del(VBSSO_NAMED_EVENT_FIELD_SHOW_LOGIN_FORM_WIDGET);
    variable_del(VBSSO_PLATFORM_FOOTER_LINK_PROPERTY);

    variable_del(VBSSO_NAMED_EVENT_FIELD_LID);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_REGISTER_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_AVATAR_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL);
    variable_del(VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME);
    variable_del(VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD);
    variable_del(VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL);
}

function vbsso_requirements() {
    $t = get_t();
    $extensions = vbsso_verify_loaded_extensions();
    $requirements = array();

    if (count($extensions)) {
        foreach ($extensions as $ext) {
            $requirements[$ext]['title'] = $t($ext);
            $requirements[$ext]['value'] = $t('Not Installed');
            $requirements[$ext]['severity'] = REQUIREMENT_ERROR;
            $requirements[$ext]['description'] = $t('The following PHP extension are required to be installed: ' . $ext);
        }
    }

    return $requirements;
}
