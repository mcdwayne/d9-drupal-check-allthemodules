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

require_once(dirname(__FILE__) . '/sharedapi.php');

if (!defined('VBSSO_SHARED')) {
    define ('VBSSO_PRODUCT_VERSION_1_4_13', '1.4.13');
    define ('VBSSO_PRODUCT_VERSION_1_4_12', '1.4.12');
    define ('VBSSO_PRODUCT_VERSION_1_4_11', '1.4.11');
    define ('VBSSO_PRODUCT_VERSION_1_4_10', '1.4.10');
    define ('VBSSO_PRODUCT_VERSION_1_4_9', '1.4.9');
    define ('VBSSO_PRODUCT_VERSION_1_4_8', '1.4.8');
    define ('VBSSO_PRODUCT_VERSION_1_4_7', '1.4.7');
    define ('VBSSO_PRODUCT_VERSION_1_4_6', '1.4.6');
    define ('VBSSO_PRODUCT_VERSION_1_4_5', '1.4.5');
    define ('VBSSO_PRODUCT_VERSION_1_4_4', '1.4.4');
    define ('VBSSO_PRODUCT_VERSION_1_4_3', '1.4.3');
    define ('VBSSO_PRODUCT_VERSION_1_4_2', '1.4.2');
    define ('VBSSO_PRODUCT_VERSION_1_4_1', '1.4.1');
    define ('VBSSO_PRODUCT_VERSION_1_4', '1.4');
    define ('VBSSO_PRODUCT_VERSION_1_3', '1.3');
    define ('VBSSO_PRODUCT_VERSION_1_2', '1.2');
    define ('VBSSO_PRODUCT_VERSION_1_1', '1.1');
    define ('VBSSO_PRODUCT_VERSION_1_0', '1.0');

    define ('VBSSO_SHARED', VBSSO_PRODUCT_VERSION_1_4_13);

    define ('VBSSO_PRODUCT_ID', 'vbsso');
    define ('VBSSO_PRODUCT_NAME', 'vBSSO');

    /**
     * Product Options.
     */
    define ('VBSSO_OPTIONS_CUSTOM_LOGIN_LINK', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'custom_login_link'));
    define ('VBSSO_OPTIONS_CUSTOM_LOGOUT_LINK', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'custom_logout_link'));
    define ('VBSSO_OPTIONS_CUSTOM_REGISTER_LINK', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'custom_register_link'));

    define ('VBSSO_OPTIONS_PROFILE_FIELD_FIRST_NAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_first_name'));
    define ('VBSSO_OPTIONS_PROFILE_FIELD_LAST_NAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_last_name'));
    define ('VBSSO_OPTIONS_PROFILE_FIELD_COUNTRY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_country'));
    define ('VBSSO_OPTIONS_PROFILE_FIELD_CITY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_city'));
    define ('VBSSO_OPTIONS_PROFILE_FIELD_PHONE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_phone'));
    define ('VBSSO_OPTIONS_PROFILE_FIELD_BIRTH', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'profile_field_birth'));

    define ('VBSSO_OPTIONS_ALLOWED_USERGROUPS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'allowed_usergroups'));
    define ('VBSSO_OPTIONS_ASSOCIATED_USERGROUPS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'associated_usergroups'));
    define ('VBSSO_OPTIONS_LOGIN_ACCESS_SETTINGS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'login_access_settings'));

    define ('VBSSO_OPTIONS_LOGGING_MODE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'logging_mode'));
    define ('VBSSO_OPTIONS_LOGGING_LEVEL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'logging_level'));
    define ('VBSSO_OPTIONS_LOGGING_EMAIL_NOTIFICATIONS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'logging_email_notifications'));
    define ('VBSSO_OPTIONS_LOGGING_EMAIL_NOTIFICATIONS_ADDRESSES', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'logging_email_notifications_addresses'));

    /**
     * Platform Config.
     */
    define ('VBSSO_CONFIG_PROPERTY_LOG', 'log');
    define ('VBSSO_CONFIG_PROPERTY_SIMPLE_LOGGING', 'simple-logging');
    define ('VBSSO_CONFIG_PROPERTY_OVERRIDE_LINKS', 'override-links');
    define ('VBSSO_CONFIG_PROPERTY_VB_REGISTER_REDIRECT', 'vbulletin-registration-redirect');

    /**
     * Platform Errors.
     */
    define ('VBSSO_PLATFORM_ERROR_DISABLED', 'You are trying to connect a platform where vBSSO extension is disabled. Please enable vBSSO extension on that platform and try again.');

    /**
     * Footer Link Options.
     */
    define ('VBSSO_PLATFORM_DESCRIPTION_HTML', 'Provides universal Secure Single Sign-On between vBulletin and different popular platforms. The product provided by <a href="http://www.vbsso.com/">' . VBSSO_PRODUCT_NAME . '.</a>');

    define ('VBSSO_PLATFORM_FOOTER_LINK_PROPERTY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'footer_link'));
    define ('VBSSO_PLATFORM_FOOTER_LINK_DESCRIPTION_HTML', 'To remove the footer link back to this project, you may purchase a <a href="http://www.vbsso.com/documentation/#remove-footer-link">Branding Free license</a>. Your support is used to maintain this project.');
    define ('VBSSO_PLATFORM_FOOTER_LINK_HTML', '<div align="center" style="z-index: 3;color: #777777;clear:both; position: relative;">Single Sign On provided by <a href="http://www.vbsso.com/">vBSSO</a></div>');


    define ('VBSSO_PLATFORM_FOOTER_GA_HTML', "<script type=\"text/javascript\"> var _gaq = _gaq || []; _gaq.push(['_setAccount', 'UA-25094208-2']); /*_gaq.push(['_trackPageview']);*/ /*track_event_marker*/ (function() { var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true; ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js'; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s); })(); </script>\n");
    function VBSSO_PLATFORM_FOOTER_GA_HTML($platforms, $action = '') {
        $platforms = (is_array($platforms)) ? $platforms : array($platforms);
        if (!empty($action)) {
            switch ($action) {
                case ('login'):
                    $action = 'A/';
                    break;
                case ('register'):
                    $action = 'R/';
            }
        }

        $ga_code = VBSSO_PLATFORM_FOOTER_GA_HTML;
        $marker = '/*track_event_marker*/';

        foreach ($platforms as $platform) {
            $ga_code = str_replace($marker, '_gaq.push([\'_trackEvent\', \'' . $action . $platform . '\', window.location.host, window.location.href]); ' . $marker, $ga_code);
        }

        return str_replace($marker, '', $ga_code);
    }

    define ('VBSSO_PLATFORM_FOOTER_LINK_SHOW_NONE', 0);
    define ('VBSSO_PLATFORM_FOOTER_LINK_SHOW_EVERYWHERE', 1);
    define ('VBSSO_PLATFORM_FOOTER_LINK_SHOW_ADMIN', 2);

    function vbsso_get_platform_footer_link_options() {
        return array(
//            VBSSO_PLATFORM_FOOTER_LINK_SHOW_ADMIN => 'I want to evaluate it first (don\'t show the link for the public)',
            VBSSO_PLATFORM_FOOTER_LINK_SHOW_EVERYWHERE => 'Show',
            VBSSO_PLATFORM_FOOTER_LINK_SHOW_NONE => 'Don\'t show (I have already purchased branding Free license)',
        );
    }

    /**
     * Event Fields.
     */
    define ('VBSSO_NAMED_EVENT_FIELD_API_KEY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_API_KEY));
    define ('VBSSO_NAMED_EVENT_FIELD_API_KEY_TITLE', 'Platform Shared Key');
    define ('VBSSO_NAMED_EVENT_FIELD_API_KEY_WARNING', 'Please unconnect this platform to change shared key');

    define ('VBSSO_NAMED_EVENT_FIELD_LID', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LID));
    define ('VBSSO_NAMED_EVENT_FIELD_LID_TITLE', 'Plugin Id');

    define ('VBSSO_NAMED_EVENT_FIELD_LISTENER_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LISTENER_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_LISTENER_URL_TITLE', 'Platform Address');

    define ('VBSSO_NAMED_EVENT_FIELD_TIMEOUT', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_TIMEOUT));
    define ('VBSSO_NAMED_EVENT_FIELD_MUID', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_MUID));

    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'login_through_vb_page'));
    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_THROUGH_VB_PAGE_TITLE', 'Login Through vBulletin Page');

    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'vb_author_profile_url'));
    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_AUTHOR_PROFILE_TITLE', 'View Member Profile in vBulletin');

    define ('VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'edit_member_profile_in_vbulletin'));
    define ('VBSSO_NAMED_EVENT_FIELD_EDIT_MEMBER_PROFILE_IN_VBULLETIN_TITLE', 'Edit Member Profile in vBulletin');

    define ('VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'edit_profile_in_vbulletin'));
    define ('VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN_TITLE', 'Edit Profile in vBulletin');

    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'vb_profile_url'));
    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE_TITLE', 'View My Profile in vBulletin');

    define ('VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'fetch_avatars'));
    define ('VBSSO_NAMED_EVENT_FIELD_FETCH_AVATARS_TITLE', 'Fetch and Show vBulletin Avatars');

    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_LOGIN_FORM_WIDGET', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'login_form_widget'));
    define ('VBSSO_NAMED_EVENT_FIELD_SHOW_LOGIN_FORM_WIDGET_TITLE', 'Show vBSSO Login Form Widget');

    define ('VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'admin_role_name'));
    define ('VBSSO_NAMED_EVENT_FIELD_ADMINISTRATOR_ROLE_NAME_TITLE', 'Name of Administrator Role');

    define ('VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, 'usergroups_assoc'));
    define ('VBSSO_NAMED_EVENT_FIELD_USERGROUPS_ASSOC_TITLE', 'Usergroups Associations');

    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_FIELDS', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_FIELDS));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_FIRST_NAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_FIRST_NAME));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_LAST_NAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_LAST_NAME));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_COUNTRY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_COUNTRY));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_CITY', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_CITY));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_PHONE', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_PHONE));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_BIRTH', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_BIRTH));

    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LOGIN_VBULLETIN_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL_TITLE', 'Login vBulletin Url');

    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LOGIN_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_LOGIN_URL_TITLE', 'Login Url');

    define ('VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LOGOUT_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL_TITLE', 'Logout Url');

    define ('VBSSO_NAMED_EVENT_FIELD_REGISTER_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_REGISTER_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_REGISTER_URL_TITLE', 'Register Url');

    define ('VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_LOSTPASSWORD_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL_TITLE', 'Lost Password Url');

    define ('VBSSO_NAMED_EVENT_FIELD_AVATAR_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_AVATAR_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_AVATAR_URL_TITLE', 'Avatar Url');

    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_PROFILE_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_PROFILE_URL_TITLE', 'Profile Url');

    define ('VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_USERGROUPS_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL_TITLE', 'Get User Groups Url');

    define ('VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_BAA_USERNAME));
    define ('VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME_TITLE', 'Basic access authentication username');

    define ('VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_BAA_PASSWORD));
    define ('VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD_TITLE', 'Basic access authentication password');

    define ('VBSSO_NAMED_EVENT_FIELD_USER_UNREAD_STATS_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_USER_UNREAD_STATS_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_USER_UNREAD_STATS_URL_TITLE', 'Unread Stats Url');

    define ('VBSSO_NAMED_EVENT_FIELD_STATS_URL', SHAREDAPI_NAME_DEFINITION(VBSSO_PRODUCT_ID, SHAREDAPI_EVENT_FIELD_STATS_URL));
    define ('VBSSO_NAMED_EVENT_FIELD_STATS_URL_TITLE', 'Stats Url');

    /*
     * Platform Configuration methods.
     */
    function vbsso_get_platform_config($platform) {
        static $config = array();

        if (!isset($config[$platform]) || $config[$platform] === NULL) {
            $platforms_ids = sharedapi_get_platforms_ids();

            $config[$platform] = array(
                VBSSO_CONFIG_PROPERTY_LOG => TRUE,
                VBSSO_CONFIG_PROPERTY_OVERRIDE_LINKS => TRUE,
                VBSSO_CONFIG_PROPERTY_SIMPLE_LOGGING => FALSE,
                VBSSO_CONFIG_PROPERTY_VB_REGISTER_REDIRECT => FALSE
            );

            $cfg = array();
            $platform_id = isset($platforms_ids[$platform]) ? $platforms_ids[$platform] : NULL;

            if (count($cfg)) {
                $config[$platform] = array_merge($config[$platform], $cfg);
            }

            if ($platform_id && function_exists('vbsso_get_' . $platform_id . '_custom_config')) {
                $custom_config = call_user_func('vbsso_get_' . $platform_id . '_custom_config');
                $config[$platform] = array_merge($config[$platform], $custom_config);
            }
        }

        return $config[$platform];
    }

    function vbsso_get_platform_config_property($platform, $property, $default = '') {
        static $config_platform;

        if (!isset($config_platform[$platform])) {
            $config_platform[$platform] = vbsso_get_platform_config($platform);
        }

        $config = $config_platform[$platform];
        return isset($config[$property]) ? $config[$property] : $default;
    }

    function vbsso_get_supported_api_properties() {
        static $properties;

        if (!$properties) {
            $properties = array(
                VBSSO_NAMED_EVENT_FIELD_LID => array('field' => SHAREDAPI_EVENT_FIELD_LID, 'title' => VBSSO_NAMED_EVENT_FIELD_LID_TITLE),
                VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL => array('field' => SHAREDAPI_EVENT_FIELD_LOGIN_VBULLETIN_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_LOGIN_VBULLETIN_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_LOGIN_URL => array('field' => SHAREDAPI_EVENT_FIELD_LOGIN_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_LOGIN_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL => array('field' => SHAREDAPI_EVENT_FIELD_LOGOUT_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_REGISTER_URL => array('field' => SHAREDAPI_EVENT_FIELD_REGISTER_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_REGISTER_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL => array('field' => SHAREDAPI_EVENT_FIELD_LOSTPASSWORD_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_AVATAR_URL => array('field' => SHAREDAPI_EVENT_FIELD_AVATAR_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_AVATAR_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_PROFILE_URL => array('field' => SHAREDAPI_EVENT_FIELD_PROFILE_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_PROFILE_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL => array('field' => SHAREDAPI_EVENT_FIELD_USERGROUPS_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_USERGROUPS_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME => array('field' => SHAREDAPI_EVENT_FIELD_BAA_USERNAME, 'title' => VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME_TITLE),
                VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD => array('field' => SHAREDAPI_EVENT_FIELD_BAA_PASSWORD, 'title' => VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD_TITLE),
                VBSSO_NAMED_EVENT_FIELD_USER_UNREAD_STATS_URL => array('field' => SHAREDAPI_EVENT_FIELD_USER_UNREAD_STATS_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_USER_UNREAD_STATS_URL_TITLE),
                VBSSO_NAMED_EVENT_FIELD_STATS_URL => array('field' => SHAREDAPI_EVENT_FIELD_STATS_URL, 'title' => VBSSO_NAMED_EVENT_FIELD_STATS_URL_TITLE)
            );
        }

        return $properties;
    }

    /*
     * Supported profile fields.
     */
    function vbsso_get_supported_profile_fields() {
        return array(
            SHAREDAPI_EVENT_FIELD_PROFILE_FIRST_NAME => VBSSO_OPTIONS_PROFILE_FIELD_FIRST_NAME,
            SHAREDAPI_EVENT_FIELD_PROFILE_LAST_NAME => VBSSO_OPTIONS_PROFILE_FIELD_LAST_NAME,
            SHAREDAPI_EVENT_FIELD_PROFILE_COUNTRY => VBSSO_OPTIONS_PROFILE_FIELD_COUNTRY,
            SHAREDAPI_EVENT_FIELD_PROFILE_CITY => VBSSO_OPTIONS_PROFILE_FIELD_CITY,
            SHAREDAPI_EVENT_FIELD_PROFILE_PHONE => VBSSO_OPTIONS_PROFILE_FIELD_PHONE,
            SHAREDAPI_EVENT_FIELD_PROFILE_BIRTH => VBSSO_OPTIONS_PROFILE_FIELD_BIRTH,
        );
    }

    /*
     * Verification methods.
     */
    function vbsso_verify_loaded_extensions() {
        $extensions = array('curl', 'mcrypt', 'zip');

        $nloaded = array();
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $nloaded[] = $extension;
            }
        }

        return $nloaded;
    }

    function vbsso_get_final_destination($event) {
        return sharedapi_get_final_destination(VBSSO_PRODUCT_ID, $event);
    }

    function vbsso_get_destination($event, $r = 'r', $reset = FALSE) {
        return sharedapi_get_destination(VBSSO_PRODUCT_ID, $event, $r, $reset);
    }

    function vbsso_get_final_lid($event) {
        return sharedapi_get_final_lid(VBSSO_PRODUCT_ID, $event);
    }

    function vbsso_get_lid($event, $r = 'r', $reset = FALSE) {
        return sharedapi_get_lid(VBSSO_PRODUCT_ID, $event, $r, $reset);
    }

    function vbsso_capture_destination($event) {
        sharedapi_capture_destination(VBSSO_PRODUCT_ID, $event, VBSSO_PRODUCT_API_PATH);
    }

    function vbsso_log($data, $file = 'default', $path = NULL) {
        if (vbsso_get_platform_config_property(SHAREDAPI_PLATFORM_VBULLETIN, VBSSO_CONFIG_PROPERTY_LOG, TRUE)) {
            sharedapi_log($data, $file == 'default' ? 'vbsso_' . date('Y-m-d', time()) . '.log' : $file, $path);
        }
    }
}