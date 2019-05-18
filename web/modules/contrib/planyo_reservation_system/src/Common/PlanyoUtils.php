<?php

namespace Drupal\planyo\Common;

use GuzzleHttp\Client;
use Drupal\Core\Url;
use Drupal;

class PlanyoUtils {
  public static function planyo_send_request($fields) {
    $url = $fields['ulap_url'];
    $parts = parse_url($url);
    $host = $parts['host'];
    
    define ('USE_SANDBOX', 0); // set this to 1 to use sandbox.planyo.com (test version), or 0 for planyo.com
    if (constant('USE_SANDBOX')) {
      $url = str_replace("http://www.planyo.com", "http://sandbox.planyo.com", $url);
      $url = str_replace("https://www.planyo.com", "http://sandbox.planyo.com", $url);
    }
    
    if ($host != 'www.planyo.com' && $host != 'sandbox.planyo.com')
      return "Error: Call to $url not allowed";
    
    $data = '';
    if ($fields && count($fields) > 0) {
      foreach (array_keys($fields) as $key) {
        if ($key == 'language' && (strtoupper($fields[$key]) == 'AU' || strtoupper($fields[$key]) == 'AUTO') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
          $value = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        else
          $value = $fields[$key];
        $data = $data . "$key=" . rawurlencode($value);
        $data .= '&';
      }
    }
    $data .= 'modver=2.7';
    $ip = Drupal::request()->getClientIp();
    if ($ip)
      $data .= "&client_ip=$ip";
    $data .= "&user_agent=" . rawurlencode($_SERVER['HTTP_USER_AGENT']);
    
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');

    try {
      $client = new \GuzzleHttp\Client();
      $response = $client->post($url, ['query'=>$data, 'headers'=>$headers]);
      $data = $response->getBody();
    }
    catch (Exception $e) {
      return "";
    }

    return $data;
  }

  public static function variable_get($item, $default) {
    $config = Drupal::config('planyo.settings');
    $value = $config->get($item);
    if ($value === null)
      $value = $default;
    return $value;
  }

  public static function planyo_get_attribs_as_array($str) {
    $array = array();
    $pairs = explode('&', $str);  
    if ($pairs && is_array($pairs)) {  
      foreach($pairs as $pair) {
        if (is_string($pair) && strpos($pair, '=') !== false) { 
          list($name, $value) = explode('=', $pair, 2);
          $name = trim($name);
          $value = trim($value);
          if (is_string($name) && strlen($name) > 0)
            $array[$name] = $value;
        }
      }
    }
    return $array;
  }

  public static function planyo_should_use_attribs() {
    return !(isset($_GET['mode']) || isset($_GET['ppp_mode']) || isset($_GET['presentation_mode']) || isset($_GET['prefill']) || isset($_GET['submitted']) || isset($_POST['ppp_mode']) || isset($_POST['mode']) || isset($_POST['presentation_mode']) || isset($_POST['prefill']) || isset($_POST['submitted']));
  }

  public static function planyo_disable_mode_in_attribs(&$attribs) {
    $attribs = str_replace("ppp_mode=", "ppp_mode_orig=", $attribs);
    $attribs = str_replace("mode=", "mode_orig=", $attribs);
    $attribs = str_replace("&resource_id=", "&resource_id_orig=", $attribs);
    $attribs = str_replace("?resource_id=", "?resource_id_orig=", $attribs);
  }

  public static function get_planyo_language() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $default_language = PlanyoUtils::variable_get('planyo_language', '0');
    if (!$default_language && isset($language)) {
      $default_language = strtoupper(substr($language->language, 0, 2));
      if (!$default_language)
        $default_language = 'EN';
    }
    return $default_language;
  }

  public static function planyo_get_login_info() {
    $user = Drupal::currentUser();
    $planyo_login_info = null;
    if ($user && $user->id() && PlanyoUtils::variable_get('planyo_use_login', '0') == '1') {
      $user_fields = user_load($user->id());
      $last_name = null;
      $first_name = null;
      $email = null;
      if ($user_fields) {
        $email = @$user_fields->getEmail();
        if (@$user_fields->getDisplayName()) {
          $names = explode(" ", $user_fields->getDisplayName());
          if (is_array($names) && count($names) > 1) {
            $last_name = array_pop($names);
            $first_name = implode(" ", $names);
          }
        }
      }
      $planyo_login_info = array('email'=>$email, 'first_name'=>$first_name, 'last_name'=>$last_name, 'login_cs'=>PlanyoUtils::variable_get('planyo_login_integration_code', '') ? sha1($email . PlanyoUtils::variable_get('planyo_login_integration_code', '')) : 'nocode');
    }
    return $planyo_login_info;
  }

  public static function planyo_add_other_params($params) {
    global $planyo_attribs;
    $other_params = $_GET;
    if ($planyo_attribs && is_string($planyo_attribs)) {
      if (!PlanyoUtils::planyo_should_use_attribs())
        PlanyoUtils::planyo_disable_mode_in_attribs($planyo_attribs);
      $attrib_array = PlanyoUtils::planyo_get_attribs_as_array(strip_tags(html_entity_decode($planyo_attribs)));
      $other_params = array_merge($attrib_array, $other_params);
    }
    if ($other_params && count($other_params) > 0) {
      reset ($other_params);
      while (list ($name, $value) = each ($other_params)) {
        if (strpos ($name, 'ppp_') === 0 && !array_key_exists($name, $params)) {
          $params [$name] = $value;
        }
      }
    }
    return $params;
  }

  public static function planyo_get_param($name) {
    global $planyo_attribs;
    if (isset ($_GET[$name]))
      return $_GET[$name];
    if (isset ($_POST[$name]))
      return $_POST[$name];
    if ($planyo_attribs && is_string($planyo_attribs)) {
      if (!PlanyoUtils::planyo_should_use_attribs())
        PlanyoUtils::planyo_disable_mode_in_attribs($planyo_attribs);
      $attrib_array = PlanyoUtils::planyo_get_attribs_as_array($planyo_attribs);
      if (isset ($attrib_array[$name]))
        return $attrib_array[$name];
    }
    return null;
  }

  public static function planyo_output_resource_list(&$retarr) {
    global $planyo_site_id, $planyo_metasite_id, $planyo_feedback_url, $planyo_default_mode, $planyo_language, $planyo_resource_ordering;
    $planyo_default_mode = 'empty';
    $language = PlanyoUtils::get_planyo_language();

    if ($planyo_metasite_id && PlanyoUtils::planyo_get_param('site_id')) {
      $site_id_used = PlanyoUtils::planyo_get_param('site_id');
      $metasite_id_used = $planyo_metasite_id;
    }
    else if ($planyo_site_id && !$planyo_metasite_id) {
      $site_id_used = $planyo_site_id;
      $metasite_id_used = '';
    }
    else {
      $site_id_used = '';
      $metasite_id_used = $planyo_metasite_id;
    }

    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $retarr['#attached']['html_head'][] = array(array(
      '#tag' => 'script',
      '#value' => "var planyo_force_mode='empty';",
    ), 'planyo_scripts');
    $params = array('ulap_url'=>($https ? 'https' : 'http').'://www.planyo.com/rest/planyo-reservations.php','modver'=>'2.7','site_id'=>$site_id_used,'metasite_id'=>$metasite_id_used,'mode'=>'display_resource_list_code','feedback_url'=>$planyo_feedback_url,'language'=>$language ? $language : '', 'sort'=>PlanyoUtils::planyo_get_param('sort') ? PlanyoUtils::planyo_get_param('sort') : $planyo_resource_ordering, 'res_filter_name'=>PlanyoUtils::planyo_get_param('res_filter_name'), 'res_filter_value'=>PlanyoUtils::planyo_get_param('res_filter_value'));
    $planyo_login_info = PlanyoUtils::planyo_get_login_info();
    if (is_array($planyo_login_info)) {
      $params['login_cs'] = $planyo_login_info['login_cs'];
      $params['login_email'] = $planyo_login_info['email'];
    }
    if (isset($_COOKIE['planyo_cart_id']) && isset($_COOKIE['planyo_first_reservation_id'])) {
      $params['cart_id'] = $_COOKIE['planyo_cart_id'];
      $params['first_reservation_id'] = $_COOKIE['planyo_first_reservation_id'];
    }
    $str = PlanyoUtils::planyo_send_request(PlanyoUtils::planyo_add_other_params($params));
    PlanyoUtils::move_scripts_to_head($str, $retarr);
    return $str;
  }

  public static function move_scripts_to_head(&$body, &$retarr) {
    return;

    $js = "";
    preg_match_all('#<script(.*?)</script>#is', $body, $matches);
    foreach ($matches[0] as $value) {
      $cl = strpos($value, ">");
      $op = strrpos($value, "<");
      if ($cl !== false && $op !== false && $op > $cl) {
        $val = substr($value, $cl + 1, $op - $cl - 1);
      }
      $js .= $val;
    }
    $retarr['#attached']['html_head'][] = array(array(
      '#tag' => 'script',
      '#value' => $js,
    ), 'planyo_parsed_scripts');
    $body = preg_replace('#<script(.*?)</script>#is', '', $body); 
  }

  public static function planyo_output_site_list(&$retarr) {
    global $planyo_site_id, $planyo_metasite_id, $planyo_feedback_url, $planyo_default_mode, $planyo_language;
    $planyo_default_mode = 'empty';
    $language = $planyo_language;
    if (PlanyoUtils::planyo_get_param('lang'))
      $language = PlanyoUtils::planyo_get_param('lang');
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $retarr['#attached']['html_head'][] = array(array(
      '#tag' => 'script',
      '#value' => "var planyo_force_mode='empty';",
    ), 'planyo_scripts');
    $params = array('ulap_url'=>($https ? 'https' : 'http').'://www.planyo.com/rest/planyo-reservations.php','modver'=>'2.7','site_id'=>'','metasite_id'=>$planyo_metasite_id,'mode'=>'display_site_list_code','feedback_url'=>$planyo_feedback_url,'language'=>$language ? $language : '', 'sort'=>PlanyoUtils::planyo_get_param('sort') ? PlanyoUtils::planyo_get_param('sort') : '', 'cal_filter_name'=>PlanyoUtils::planyo_get_param('cal_filter_name'), 'cal_filter_value'=>PlanyoUtils::planyo_get_param('cal_filter_value'));
    $planyo_login_info = PlanyoUtils::planyo_get_login_info();
    if (is_array($planyo_login_info)) {
      $params['login_cs'] = $planyo_login_info['login_cs'];
      $params['login_email'] = $planyo_login_info['email'];
    }
    if (isset($_COOKIE['planyo_cart_id']) && isset($_COOKIE['planyo_first_reservation_id'])) {
      $params['cart_id'] = $_COOKIE['planyo_cart_id'];
      $params['first_reservation_id'] = $_COOKIE['planyo_first_reservation_id'];
    }

    $str = PlanyoUtils::planyo_send_request(PlanyoUtils::planyo_add_other_params($params));
    PlanyoUtils::move_scripts_to_head($str, $retarr);
    return $str;
  }

  public static function planyo_output_resource_details(&$retarr) {
    global $planyo_site_id, $planyo_metasite_id, $planyo_feedback_url, $planyo_default_mode, $planyo_language;
    $planyo_default_mode = 'empty';
    $language = $planyo_language;
    if (PlanyoUtils::planyo_get_param('lang'))
      $language = PlanyoUtils::planyo_get_param('lang');
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $retarr['#attached']['html_head'][] = array(array(
      '#tag' => 'script',
      '#value' => "var planyo_force_mode='empty';",
    ), 'planyo_scripts');
    $params = array('ulap_url'=>($https ? 'https' : 'http').'://www.planyo.com/rest/planyo-reservations.php','modver'=>'2.7','site_id'=>($planyo_site_id && !$planyo_metasite_id ? $planyo_site_id : ""),'metasite_id'=>($planyo_metasite_id ? $planyo_metasite_id : ""),'resource_id'=>PlanyoUtils::planyo_get_param('resource_id'), 'mode'=>'display_single_resource_code','feedback_url'=>$planyo_feedback_url,'language'=>$language ? $language : '');
    $planyo_login_info = PlanyoUtils::planyo_get_login_info();
    if (is_array($planyo_login_info)) {
      $params['login_cs'] = $planyo_login_info['login_cs'];
      $params['login_email'] = $planyo_login_info['email'];
    }
    if (isset($_COOKIE['planyo_cart_id']) && isset($_COOKIE['planyo_first_reservation_id'])) {
      $params['cart_id'] = $_COOKIE['planyo_cart_id'];
      $params['first_reservation_id'] = $_COOKIE['planyo_first_reservation_id'];
    }
    $str = PlanyoUtils::planyo_send_request(PlanyoUtils::planyo_add_other_params($params));
    PlanyoUtils::move_scripts_to_head($str, $retarr);
    return $str;
  }

  public static function planyo_setup(&$retarr) {
    global $base_root, $planyo_site_id, $planyo_metasite_id, $planyo_feedback_url, $planyo_always_use_ajax, $planyo_default_mode, $planyo_language, $planyo_resource_ordering, $planyo_attribs;
    $planyo_site_id = PlanyoUtils::variable_get('planyo_site_id', 'demo');
    $planyo_metasite_id = null;
    $planyo_language = PlanyoUtils::get_planyo_language();
    $planyo_resource_ordering = PlanyoUtils::variable_get('planyo_resource_ordering', 'name');
    if (!$planyo_site_id || $planyo_site_id == 'demo')
      $planyo_site_id = 11;  // demo site
    else if (substr($planyo_site_id, 0, 1) == 'M')
      $planyo_metasite_id = substr($planyo_site_id, 1);  // metasite ID: set only for metasites

    if (PlanyoUtils::planyo_get_param('ppp_mode')) {
      $planyo_default_mode = PlanyoUtils::planyo_get_param('ppp_mode');
    }
    else if (PlanyoUtils::planyo_get_param('mode')) {
      $planyo_default_mode = PlanyoUtils::planyo_get_param('mode');
    }
    else {
      $resource_id = PlanyoUtils::planyo_get_param('resource_id');
      $presentation_off = (PlanyoUtils::planyo_get_param('presentation_mode') == '0' || (!PlanyoUtils::planyo_get_param('presentation_mode') && $planyo_default_mode == 'search'));
      if ($resource_id && (PlanyoUtils::planyo_get_param('prefill') || PlanyoUtils::planyo_get_param('submitted')))
        $planyo_default_mode = 'reserve';
      else if ($presentation_off)
        $planyo_default_mode = 'search';
      else if ($resource_id)
        $planyo_default_mode = 'resource_desc';
      else if (PlanyoUtils::planyo_get_param('presentation_mode') == '1' || !$planyo_default_mode)
        $planyo_default_mode = 'resource_list';
    }

    $planyo_always_use_ajax = true; //PlanyoUtils::variable_get('planyo_seo_friendly', '1') == '1' ? false : true;
    if (!$planyo_always_use_ajax) {
      $planyo_feedback_url = PlanyoUtils::planyo_get_param('feedback_url');
      if (!$planyo_feedback_url)
        $planyo_feedback_url = $base_root . PlanyoUtils::request_uri();

      if ($planyo_default_mode == 'resource_desc' && PlanyoUtils::planyo_get_param('resource_id'))
        return "<div id='planyo_plugin_code' class='planyo'>".PlanyoUtils::planyo_output_resource_details($retarr)."</div>";
      else if ($planyo_metasite_id && $planyo_default_mode == 'site_list' && !PlanyoUtils::planyo_get_param('site_id'))
        return "<div id='planyo_plugin_code' class='planyo'>".PlanyoUtils::planyo_output_site_list($retarr)."</div>";
      else if ($planyo_default_mode == 'resource_list')
        return "<div id='planyo_plugin_code' class='planyo'>".PlanyoUtils::planyo_output_resource_list($retarr)."</div>";
    }
    return "";
  }

  public static function request_uri($omit_query_string = FALSE) {
    if (isset($_SERVER['REQUEST_URI'])) {
      $uri = $_SERVER['REQUEST_URI'];
    }
    else {
      if (isset($_SERVER['argv'][0])) {
        $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
      }
      elseif (isset($_SERVER['QUERY_STRING'])) {
        $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
      }
      else {
        $uri = $_SERVER['SCRIPT_NAME'];
      }
    }
    // Prevent multiple slashes to avoid cross site requests via the Form API.
    $uri = '/' . ltrim($uri, '/');

    return $omit_query_string ? strtok($uri, '?') : $uri;
  }

  public static function planyo_display_block_content() {
    global $planyo_default_mode;
    global $planyo_attribs;
    global $planyo_site_id;
    static $planyo_content_written = false;
    if ($planyo_content_written)
      return array("#markup"=>"");

    $planyo_content_written = true;
    $default_language = PlanyoUtils::get_planyo_language();
    $planyo_default_mode = PlanyoUtils::variable_get('planyo_default_mode', 'resource_list');
    if ($planyo_default_mode == 'empty' && PlanyoUtils::planyo_get_param('resource_id'))
      $planyo_default_mode = 'reserve';
    $retarr = array();
    $content = PlanyoUtils::planyo_setup($retarr);
    $planyo_login_info = PlanyoUtils::planyo_get_login_info();
    if ($planyo_attribs && is_string($planyo_attribs) && !PlanyoUtils::planyo_should_use_attribs()) {
      PlanyoUtils::planyo_disable_mode_in_attribs($planyo_attribs);
    }
    $ulap_script_url = Url::fromRoute('planyo.ulap');
    $ulap_script = $ulap_script_url->toString();
    $retarr['#attached']['drupalSettings']['planyo'] = array('ulap_script' => $ulap_script,
                                          'drupal_version' => 8,
                                          'planyo_site_id' => $planyo_site_id,  // ID of your planyo site
                                          'planyo_files_location' => drupal_get_path('module', 'planyo'),  // relative or absolute directory where the planyo files are kept
                                          'extra_search_fields' => PlanyoUtils::variable_get('planyo_extra_search_fields', ''), // comma-separated extra fields in the search box
                                          'planyo_language' => $default_language,  // you can optionally change the language here, e.g. 'FR' or 'ES'
                                          'sort_fields' => PlanyoUtils::variable_get('planyo_sort_fields', 'name,price'),  // comma-separated sort fields -- a single field will hide the sort dropdown box
                                          'planyo_resource_ordering' => PlanyoUtils::variable_get('planyo_resource_ordering', 'name'), // sorting criterium for the resource list view
                                          'planyo_use_https' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false,  // set this to true if embedding planyo on a secure website (SSL)
                                          'planyo_default_mode' => $planyo_default_mode,
                                          'planyo_attribs' => ($planyo_attribs && is_string($planyo_attribs)) ? '?'.html_entity_decode($planyo_attribs) : '',
                                          'planyo_login' => $planyo_login_info);
    $retarr['#attached']['html_head'][] = array(array(
      '#tag' => 'link',
      '#attributes' => ['rel'=>'stylesheet', 'type'=>'text/css', 'href'=>'https://www.planyo.com/schemes/?calendar=' . $planyo_site_id . '&detect_mobile=auto&sel=scheme_css'],
    ), 'planyo_scripts');
    $retarr['#attached']['library'][] = 'planyo/planyo-stuff';
    $content .= "<noscript><a href='http://www.planyo.com/about-calendar.php?calendar=" . $planyo_site_id . "'>Make reservation</a><br/><br/><a href='http://www.planyo.com/'>Reservation system powered by Planyo</a></noscript>\n";
    $content .= "<div id='planyo_content' class='planyo'><img src='https://www.planyo.com/images/hourglass.gif' align='middle' /></div>";
    $retarr['#markup'] = $content;
    $retarr['#cache'] = ['contexts' => ['url', 'cookies', 'languages', 'route', 'user'], 'max-age' => 0];
    $retarr['#allowed_tags'] = array('noscript', 'style', 'script', 'a', 'abbr', 'acronym', 'address', 'article', 'aside', 'b', 'bdi', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'command', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark', 'menu', 'meter', 'nav', 'ol', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'tt', 'u', 'ul', 'var', 'wbr');

    return $retarr;
  }
}

?>