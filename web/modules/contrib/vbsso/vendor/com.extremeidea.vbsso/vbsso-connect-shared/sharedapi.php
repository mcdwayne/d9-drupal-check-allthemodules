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

if (!defined('SHAREDAPI')) {
    define ('SHAREDAPI_PRODUCT_VERSION_1_9', '1.9');
    define ('SHAREDAPI_PRODUCT_VERSION_1_8', '1.8');
    define ('SHAREDAPI_PRODUCT_VERSION_1_7', '1.7');
    define ('SHAREDAPI_PRODUCT_VERSION_1_6', '1.6');
    define ('SHAREDAPI_PRODUCT_VERSION_1_5', '1.5');
    define ('SHAREDAPI_PRODUCT_VERSION_1_4', '1.4');
    define ('SHAREDAPI_PRODUCT_VERSION_1_3', '1.3');
    define ('SHAREDAPI_PRODUCT_VERSION_1_2', '1.2');
    define ('SHAREDAPI_PRODUCT_VERSION_1_1', '1.1');
    define ('SHAREDAPI_PRODUCT_VERSION_1_0', '1.0');

    define ('SHAREDAPI', SHAREDAPI_PRODUCT_VERSION_1_9);

    define ('SHAREDAPI_PRODUCT_ID', 'sharedapi');
    define ('SHAREDAPI_PRODUCT_NAME', 'SharedAPI');
    define ('SHAREDAPI_PRODUCT_VERSION', SHAREDAPI);

    define ('SHAREDAPI_DEFAULT_API_KEY', 'simplekey');
    define ('SHAREDAPI_WRONG_API_KEY_MESSAGE', 'Wrong Shared Password');


    define ('SHAREDAPI_EVENT_FIELD_EVENT', 'e');
    define ('SHAREDAPI_EVENT_FIELD_PRODUCT', 'product');
    define ('SHAREDAPI_EVENT_FIELD_VERSION', 'version');
    define ('SHAREDAPI_EVENT_FIELD_PLUGIN_VERSION', 'plugin_version');
    define ('SHAREDAPI_EVENT_FIELD_PLUGIN_REVISION', 'plugin_revision');
    define ('SHAREDAPI_EVENT_FIELD_VERIFY', 'verify');
    define ('SHAREDAPI_EVENT_FIELD_PHP_VERSION', 'php');
    define ('SHAREDAPI_EVENT_FIELD_MEMORY_LIMIT', 'memory_limit');
    define ('SHAREDAPI_EVENT_FIELD_WP_MEMORY_LIMIT', 'wp_memory_limit');

    define ('SHAREDAPI_EVENT_FIELD_API_KEY', 'apikey');
    define ('SHAREDAPI_EVENT_FIELD_LID', 'lid');
    define ('SHAREDAPI_EVENT_FIELD_LISTENER_URL', 'listener_url');
    define ('SHAREDAPI_EVENT_FIELD_BAA_USERNAME', 'baa_username');
    define ('SHAREDAPI_EVENT_FIELD_BAA_PASSWORD', 'baa_password');

    define ('SHAREDAPI_EVENT_FIELD_LOGIN_VBULLETIN_URL', 'login_vbulletin_url');
    define ('SHAREDAPI_EVENT_FIELD_LOGIN_URL', 'login_url');
    define ('SHAREDAPI_EVENT_FIELD_LOGOUT_URL', 'logout_url');
    define ('SHAREDAPI_EVENT_FIELD_REGISTER_URL', 'register_url');
    define ('SHAREDAPI_EVENT_FIELD_LOSTPASSWORD_URL', 'lostpassword_url');
    define ('SHAREDAPI_EVENT_FIELD_AVATAR_URL', 'avatar_url');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_URL', 'profile_url');
    define ('SHAREDAPI_EVENT_FIELD_USERGROUPS_URL', 'usergroups_url');
    define ('SHAREDAPI_EVENT_FIELD_USER_UNREAD_STATS_URL', 'user_unread_stats_url');
    define ('SHAREDAPI_EVENT_FIELD_STATS_URL', 'stats_url');

    define ('SHAREDAPI_EVENT_FIELD_STAT_PM', 'pm'); //personal messages
    define ('SHAREDAPI_EVENT_FIELD_STAT_VM', 'vm'); //visitors messages
    define ('SHAREDAPI_EVENT_FIELD_STAT_FR', 'fr'); //friend requests
    define ('SHAREDAPI_EVENT_FIELD_STAT_PC', 'pc'); //pictures comments

    define ('SHAREDAPI_EVENT_FIELD_DESTINATION', 'd');
    define ('SHAREDAPI_EVENT_FIELD_TIMEOUT', 'timeout');
    define ('SHAREDAPI_EVENT_FIELD_MUID', 'muid');
    define ('SHAREDAPI_EVENT_FIELD_REMEMBERME', 'remember-me');

    define ('SHAREDAPI_EVENT_FIELD_DATA', 'data');
    define ('SHAREDAPI_EVENT_FIELD_ERROR', 'error');
    define ('SHAREDAPI_EVENT_FIELD_ERROR_CODE', 'code');
    define ('SHAREDAPI_EVENT_FIELD_ERROR_MESSAGE', 'message');
    define ('SHAREDAPI_EVENT_FIELD_ERROR_DATA', 'data');

    define ('SHAREDAPI_EVENT_FIELD_USERLIST', 'userlist');
    define ('SHAREDAPI_EVENT_FIELD_USERID', 'userid');
    define ('SHAREDAPI_EVENT_FIELD_USERGROUPS', 'usergroup');
    define ('SHAREDAPI_EVENT_FIELD_USERGROUPS2', 'usergroup2');
    define ('SHAREDAPI_EVENT_FIELD_USERNAME', 'username');
    define ('SHAREDAPI_EVENT_FIELD_USERNAME2', 'username2');
    define ('SHAREDAPI_EVENT_FIELD_EMAIL', 'email');
    define ('SHAREDAPI_EVENT_FIELD_EMAIL2', 'email2');
    define ('SHAREDAPI_EVENT_FIELD_PASSWORD', 'password');

    define ('SHAREDAPI_EVENT_FIELD_PROFILE_FIELDS', 'profile_fields');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_FIRST_NAME', 'profile_firstname');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_LAST_NAME', 'profile_lastname');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_COUNTRY', 'profile_country');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_CITY', 'profile_city');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_PHONE', 'profile_phone');
    define ('SHAREDAPI_EVENT_FIELD_PROFILE_BIRTH', 'profile_birth');

    define ('SHAREDAPI_EVENT_UNKNOWN', 0);
    define ('SHAREDAPI_EVENT_VERIFY', 1);

    define ('SHAREDAPI_EVENT_LOGIN', 2);
    define ('SHAREDAPI_EVENT_AUTHENTICATION', 3);
    define ('SHAREDAPI_EVENT_SESSION', 4);
    define ('SHAREDAPI_EVENT_LOGOUT', 5);
    define ('SHAREDAPI_EVENT_REGISTER', 6);
    define ('SHAREDAPI_EVENT_CREDENTIALS', 7);
    define ('SHAREDAPI_EVENT_PROFILE_FIELDS', 8);
    define ('SHAREDAPI_EVENT_CONFLICT_USERS', 9);

    define ('SHAREDAPI_EVENT_LAST', 50);

    function sharedapi_get_events() {
        static $events;

        if (!$events) {
            $events = array(
                SHAREDAPI_EVENT_UNKNOWN => 'unknown',
                SHAREDAPI_EVENT_VERIFY => 'verify',
                SHAREDAPI_EVENT_LOGIN => 'login',
                SHAREDAPI_EVENT_AUTHENTICATION => 'i',
                SHAREDAPI_EVENT_SESSION => 'session',
                SHAREDAPI_EVENT_LOGOUT => 'o',
                SHAREDAPI_EVENT_REGISTER => 'register',
                SHAREDAPI_EVENT_CREDENTIALS => 'credentials',
                SHAREDAPI_EVENT_PROFILE_FIELDS => 'profile-fields',
                SHAREDAPI_EVENT_CONFLICT_USERS => 'conflict_users'
            );
        }

        return $events;
    }

    /**
     * Product constants
     */
    define ('SHAREDAPI_PLATFORM_UNKNOWN', 0);
    define ('SHAREDAPI_PLATFORM_VBULLETIN', 1);
    define ('SHAREDAPI_PLATFORM_INTERSPIRESHOPPINGCART', 2);
    define ('SHAREDAPI_PLATFORM_MOODLE', 3);
    define ('SHAREDAPI_PLATFORM_WORDPRESS', 4);
    define ('SHAREDAPI_PLATFORM_JOOMLA', 5);
    define ('SHAREDAPI_PLATFORM_DRUPAL', 6);
    define ('SHAREDAPI_PLATFORM_PRESTASHOP', 7);
    define ('SHAREDAPI_PLATFORM_MAGENTO', 8);
    define ('SHAREDAPI_PLATFORM_MEDIAWIKI', 9);
    define ('SHAREDAPI_PLATFORM_DOKUWIKI', 10);

    function sharedapi_get_platforms($platform = null) {
        static $platforms;

        if (!$platforms) {
            $platforms = array(
                SHAREDAPI_PLATFORM_UNKNOWN => 'Unknown',
                SHAREDAPI_PLATFORM_VBULLETIN => 'vBulletin',
                SHAREDAPI_PLATFORM_INTERSPIRESHOPPINGCART => 'Interspire Shopping Cart',
                SHAREDAPI_PLATFORM_MOODLE => 'Moodle',
                SHAREDAPI_PLATFORM_WORDPRESS => 'WordPress',
                SHAREDAPI_PLATFORM_JOOMLA => 'Joomla',
                SHAREDAPI_PLATFORM_DRUPAL => 'Drupal',
                SHAREDAPI_PLATFORM_PRESTASHOP => 'Prestashop',
                SHAREDAPI_PLATFORM_MAGENTO => 'Magento',
                SHAREDAPI_PLATFORM_MEDIAWIKI => 'Mediawiki',
                SHAREDAPI_PLATFORM_DOKUWIKI => 'DokuWiki'
            );
        }

        return $platform ? $platforms[$platform] : $platforms;
    }

    function sharedapi_get_platforms_ids() {
        static $platforms;

        if (!$platforms) {
            $platforms = array(
                SHAREDAPI_PLATFORM_UNKNOWN => 'unknown',
                SHAREDAPI_PLATFORM_VBULLETIN => 'vbulletin',
                SHAREDAPI_PLATFORM_INTERSPIRESHOPPINGCART => 'interspireshoppingcart',
                SHAREDAPI_PLATFORM_MOODLE => 'moodle',
                SHAREDAPI_PLATFORM_WORDPRESS => 'wordpress',
                SHAREDAPI_PLATFORM_JOOMLA => 'joomla',
                SHAREDAPI_PLATFORM_DRUPAL => 'drupal',
                SHAREDAPI_PLATFORM_PRESTASHOP => 'prestashop',
                SHAREDAPI_PLATFORM_MAGENTO => 'magento',
                SHAREDAPI_PLATFORM_MEDIAWIKI => 'mediawiki',
                SHAREDAPI_PLATFORM_DOKUWIKI => 'dokuwiki'
            );
        }

        return $platforms;
    }

    function SHAREDAPI_NAME_DEFINITION($brand, $definition) {
        return $brand . '_' . $definition;
    }

    /**
     * GPC helpers.
     **/
    function sharedapi_extract_gpc_variable($source, $name, $default = '') {
        $v = $default;

        if (is_array($name)) {
            $v = array();
            foreach ($name as $n) {
                if (isset($source[$n])) {
                    $v[$n] = $source[$n];
                }
            }
        } else {
            if (isset($source[$name])) {
                $v = $source[$name];
            }
        }

        return $v;
    }

    function sharedapi_gpc_variable($name, $default = '', $r = 'g') {
        $v = $default;

        switch ($r) {
            case 'g':
                $v = sharedapi_extract_gpc_variable($_GET, $name, $default);
                break;
            case 'p':
                $v = sharedapi_extract_gpc_variable($_POST, $name, $default);
                break;
            case 'c':
                $v = sharedapi_extract_gpc_variable($_COOKIE, $name, $default);
                break;
            case 's':
                $v = sharedapi_extract_gpc_variable($_SERVER, $name, $default);
                break;
        }

        return $v;
    }

    /**
     * Trying to extract destination from the following sources in a certain order.
     * Check in url(destination)
     * Check in header(referer)
     * Check in session
     * */
    function sharedapi_capture_gpc_variable($name, $gname, $event, $r = 'r', $reset = false) {
        $variable = '';

        for ($i = 0; $i < strlen($r); $i++) {
            $ch = $r[$i];
            switch ($ch) {
                case 'r':
                    if (empty($variable) && isset($_SERVER['HTTP_REFERER'])) {
                        $variable = $_SERVER['HTTP_REFERER'];
                    }
                    break;
                case 's':
                    if (empty($variable) && isset($_SESSION[$name])) {
                        $variable = $_SESSION[$name];
                    }
                    break;
                case 'c':
                    if (empty($variable) && isset($_COOKIE[$name])) {
                        $variable = sharedapi_gpc_variable($name, '', 'c');
                    }
                    break;
                case 'g':
                    if (empty($variable) && isset($_GET[$gname])) {
                        $variable = sharedapi_gpc_variable($gname, '', 'g');
                    }
                    break;
                case 'p':
                    if (empty($variable) && isset($_GET[$gname])) {
                        $variable = sharedapi_gpc_variable($gname, '', 'p');
                    }
                    break;
            }
        }

        if ($reset) {
            setcookie($name, $_SESSION[$name] = '', 0, '/');
        }

        if (strpos($variable, $event . ':') !== false) {
            $variable = substr($variable, strlen($event . ':'));
        } else /*if (SHAREDAPI_EVENT_UNKNOWN == $event)*/ {
            if (preg_match('/^\d+:(.*)/', $variable, $matches)) {
                $variable = $matches[1];
            }
        }

        return $variable;
    }

    function sharedapi_get_destination($pid, $event, $r = 'r', $reset = false) {
        return sharedapi_capture_gpc_variable(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_DESTINATION), SHAREDAPI_EVENT_FIELD_DESTINATION, $event, $r, $reset);
    }

    function sharedapi_get_final_destination($pid, $event) {
        sharedapi_get_final_lid($pid, $event);
        return sharedapi_capture_gpc_variable(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_DESTINATION), SHAREDAPI_EVENT_FIELD_DESTINATION, $event, 'sc', true);
    }

    function sharedapi_get_lid($pid, $event, $r = 'r', $reset = false) {
        return sharedapi_capture_gpc_variable(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_LID), SHAREDAPI_EVENT_FIELD_LID, $event, $r, $reset);
    }

    function sharedapi_get_final_lid($pid, $event) {
        return sharedapi_capture_gpc_variable(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_LID), SHAREDAPI_EVENT_FIELD_LID, $event, 'sc', true);
    }

    function sharedapi_capture_destination($pid, $event, $api_path) {
        $expire = time() + 60 * 4; // expire in 4 minutes.

        $destination = sharedapi_get_destination($pid, $event, 'gr');
        if (!empty($destination) && strpos($destination, $api_path) === false) {
            setcookie(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_DESTINATION), $_SESSION[SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_DESTINATION)] = $event . ':' . $destination, $expire, '/');
        }

        $lid = sharedapi_get_lid($pid, $event, 'g');
        if (!empty($lid)) {
            setcookie(SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_LID), $_SESSION[SHAREDAPI_NAME_DEFINITION($pid, SHAREDAPI_EVENT_FIELD_LID)] = $event . ':' . $lid, $expire, '/');
        }
    }

    /**
     * Encryption helpers.
     **/
    function sharedapi_encrypt($key, $data) {
        $key = sharedapi_get_valid_key($key);
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    function sharedapi_decrypt($key, $data) {
        $key = sharedapi_get_valid_key($key);
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($data), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    function sharedapi_encode_data($key, $json) {
        $data = json_encode($json);
        return sharedapi_encrypt($key, $data);
    }

    function sharedapi_decode_data($key, $data) {
        $data = sharedapi_decrypt($key, $data);
        return json_decode($data, true);
    }

    /* 
     * The fuction returns key with proper length. Only 16, 24, 32 accepted since PHP 5.6 for MCRYPT_RIJNDAEL_128
     * Invalid key and iv sizes are no longer accepted.
     */
    function sharedapi_get_valid_key($key) {
        $accepted_length = array(16, 24, 32);

        //1st case: key has valid length
        if (in_array(strlen($key), $accepted_length)) {
            return $key;
        }

        //2nd case: key length is less than MAX allowed
        foreach ($accepted_length as $length) {
            if (strlen($key) < $length) {
                return str_pad($key, $length, "\0");
            }
        }

        //3d case: key length is greater than MAX allowed
        if (strlen($key) > end($accepted_length)) {
            return substr($key, 0, end($accepted_length));
        }
    }

    /**
     * DATA helpers.
     **/
    function sharedapi_is_error_data_item($data) {
        return is_array($data) && isset($data[SHAREDAPI_EVENT_FIELD_ERROR_MESSAGE]);
    }

    function sharedapi_data_handler($product, $version, $vbsso_version, $shared_key, $callback) {
        $ret = null;

        sharedapi_set_cookie_policy();

        $json = sharedapi_accept_data($shared_key);
        if (is_array($json) && isset($json[SHAREDAPI_EVENT_FIELD_EVENT])) {
            if (array_key_exists($json[SHAREDAPI_EVENT_FIELD_EVENT], $callback) && function_exists($callback[$json[SHAREDAPI_EVENT_FIELD_EVENT]])) {
                $ret = call_user_func($callback[$json[SHAREDAPI_EVENT_FIELD_EVENT]], $json);
            }
        } else {
            $ret[SHAREDAPI_EVENT_FIELD_ERROR] = $json;
        }

        $ret = $ret && is_array($ret) ? $ret : null;

        echo sharedapi_build_response($product, $version, $vbsso_version,
            $ret && isset($ret[SHAREDAPI_EVENT_FIELD_DATA]) ? $ret[SHAREDAPI_EVENT_FIELD_DATA] : null,
            $ret && isset($ret[SHAREDAPI_EVENT_FIELD_ERROR]) ? $ret[SHAREDAPI_EVENT_FIELD_ERROR] : null,
            $shared_key);
        exit;
    }

    function sharedapi_accept_data($key, $data = '', $is_response = false) {
        //Removes UTF-8 Byte order mark (BOM)
        if (substr($data, 0, 3) == pack('CCC', 239, 187, 191)) {
            $data = substr($data, 3);
        }

        if (empty($data)) {
            $data = isset($_REQUEST[SHAREDAPI_EVENT_FIELD_DATA]) ? $_REQUEST[SHAREDAPI_EVENT_FIELD_DATA] : '';
        }

        $json = array();
        if (!empty($data)) {
            $json = $is_response ? json_decode($data, true) : sharedapi_decode_data($key, $data);
            $json = ($json) ? $json : SHAREDAPI_WRONG_API_KEY_MESSAGE;
        }

        if (is_array($json) && isset($json[SHAREDAPI_EVENT_FIELD_PRODUCT]) && isset($json[SHAREDAPI_EVENT_FIELD_DATA])) {
            $json[SHAREDAPI_EVENT_FIELD_DATA] = sharedapi_decode_data($key, $json[SHAREDAPI_EVENT_FIELD_DATA]);
        }

//        return is_array($json) && (isset($json[SHAREDAPI_EVENT_FIELD_EVENT]) || isset($json[SHAREDAPI_EVENT_FIELD_PRODUCT]))
//            ? $json : false;
        return $json;
    }

    function sharedapi_build_data_item($key, $data) {
        return array(SHAREDAPI_EVENT_FIELD_DATA => sharedapi_encode_data($key, $data));
    }

    function sharedapi_build_response($product, $version, $vbsso_version, $data = null, $error = null, $key = null) {
        $response = array(
            SHAREDAPI_EVENT_FIELD_PRODUCT => $product,
            SHAREDAPI_EVENT_FIELD_VERSION => $version,
            SHAREDAPI_EVENT_FIELD_PLUGIN_VERSION => $vbsso_version,
        );

        if ($error) {
            $response[SHAREDAPI_EVENT_FIELD_ERROR] = $error;
        }

        if ($data) {
            $data = json_encode($data);
            if ($key) {
                $data = sharedapi_encrypt($key, $data);
            }

            $response[SHAREDAPI_EVENT_FIELD_DATA] = $data;
        }

        if (defined('PHP_VERSION')) {
            $response[SHAREDAPI_EVENT_FIELD_PHP_VERSION] = PHP_VERSION;
        }
        $response[SHAREDAPI_EVENT_FIELD_MEMORY_LIMIT] = ini_get('memory_limit');
        if (defined('WP_MEMORY_LIMIT')) {
            $response[SHAREDAPI_EVENT_FIELD_WP_MEMORY_LIMIT] = WP_MEMORY_LIMIT;
        }


        return json_encode($response);
    }

    function sharedapi_post($url, $fields, $username = '', $password = '', $timeout = 5, $connecttimeout = 5) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        if (!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: vBSSO'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, intval($timeout));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, intval($connecttimeout));

        $data = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_string = ($code != 200) ? curl_error($ch) : '';
        curl_close($ch);

        return array('code' => $code, 'response' => $data, 'error_string' => $error_string);
    }

    function sharedapi_http_status_error($status) {
        $status = intval($status);
        return $status >= 400;
    }

    function sharedapi_log($data, $file = 'default', $path = null) {
        if (!$path) {
            $path = dirname(__FILE__);
        }

        if ($file == 'default') {
            $file = 'sharedapi.log';
        }

        $handle = fopen($path . '/' . $file, "a+");

        $output = '';
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $output .= $key . '=' . $value . '&';
            }
        } else if (is_bool($data)) {
            $output = $data ? 'true' : 'false';
        } else {
            $output = $data;
        }

        $datetime = date('Y-m-d_H-i-s', time());
        fwrite($handle, $datetime . ': ' . $output . "\n");
        fclose($handle);
    }

    /**
     * URL helpers.
     **/
    function sharedapi_url_add_destination($url, $capture_referrer = true, $default = '', $lid = '', $query = '') {
        if (!empty($url)) {
            $referrer = '';

            if ($capture_referrer === true || $capture_referrer > 0) {
                $referrer = sharedapi_gpc_variable('HTTP_REFERER', '', 's');
            } else if ($capture_referrer !== false && $capture_referrer !== 0) {
                $referrer = sharedapi_get_server_url();
            }

            if (empty($referrer) && $default && !empty($default)) {
                $referrer = $default;
            }

            if (empty($referrer)) {
                $referrer = sharedapi_get_server_url();
            }

            if (!empty($referrer)) {
                $url = sharedapi_url_add_query($url, SHAREDAPI_EVENT_FIELD_DESTINATION . '=' . urlencode($referrer));

                if ($lid && !empty($lid)) {
                    $url = sharedapi_url_add_query($url, SHAREDAPI_EVENT_FIELD_LID . '=' . $lid);
                }

                if ($query && !empty($query) && is_string($query)) {
                    $url = sharedapi_url_add_query($url, $query);
                }
            }
        }

        return $url;
    }

    function sharedapi_url_get_referrer() {
        return sharedapi_gpc_variable('HTTP_REFERER', '', 's');
    }

    function sharedapi_url_add_lid($url, $lid = '', $query = '') {
        if (!empty($url)) {
            if ($lid && !empty($lid)) {
                $url = sharedapi_url_add_query($url, SHAREDAPI_EVENT_FIELD_LID . '=' . $lid);
            }

            if ($query && !empty($query) && is_string($query)) {
                $url = sharedapi_url_add_query($url, $query);
            }
        }

        return $url;
    }

    function sharedapi_url_add_query($url, $query) {
        if (!empty($url)) {
            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
        }

        return $url . $query;
    }

    function sharedapi_url_redirect($url) {
        if (!empty($url)) {
            header('Location: ' . $url);
        }
    }

    function sharedapi_get_server_url($uri = true) {
        if ($_SERVER['SERVER_PORT'] == '80') {
            $url = 'http://' . $_SERVER['SERVER_NAME'] . '';
        } else if (isset($_SERVER['HTTPS'])) {
            $url = 'https://' . $_SERVER['SERVER_NAME'] . '';
        } else {
            $url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '';
        }

        if ($uri === true) {
            $url .= $_SERVER['REQUEST_URI'];
        } else if (is_string($uri)) {
            $url .= $uri;
        }

        return $url;
    }

    //rsf is register_shutdown_function. Some hosting providers disable it for security reasons.
    function sharedapi_is_rsf_disabled() {
        return in_array('register_shutdown_function', explode(',', ini_get('disable_functions')));
    }

    function sharedapi_get_primary_domain($url) {
        if (empty($url)) {
            return '';
        }

        $url_host = parse_url(strtolower($url), PHP_URL_HOST);
        if (strpos('www.', $url_host) === 0) {
            $url_host = substr($url_host, 4);
        }

        $dots_count = substr_count('.', $url_host);
        if ($dots_count < 2 OR ($dots_count == 4 && ip2long($url_host))) {
            return $url_host;
        }

        $domainArray = explode('.', $url_host);
        $topLevelDomainArray = array(array_pop($domainArray), array_pop($domainArray));
        krsort($topLevelDomainArray);
        return strtolower(join('.', $topLevelDomainArray));
    }

    function sharedapi_set_cookie_policy(){
        $referer_primary_domain = sharedapi_get_primary_domain(sharedapi_gpc_variable('HTTP_REFERER', '', 's'));
        $server_primary_domain = sharedapi_get_primary_domain(sharedapi_get_server_url());

        if ($referer_primary_domain !== $server_primary_domain) {
            header("p3p:CP=\"NOI ADM DEV COM NAV OUR STP\"");
        }
    }
}