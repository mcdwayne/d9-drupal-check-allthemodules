<?php

/**
 * Class that utilizes native WordPress functions to perform actions like remote requests and DB operations.
 */
class InstapageCmsPluginWPConnector {

  /**
   * @var string $name Name of the CMS.
   */
  public $name = 'wordpress';

  /**
   * Gets plugin directory name.
   *
   * @return string Plugin's directory name.
   */
  public function getPluginDirectoryName() {
    return 'instapage';
  }

  /**
   * Gets the CMS name.
   *
   * @return string CMS name.
   */
  public function getCMSName() {
    return 'WordPress';
  }

  /**
   * Checks if current user can manage the plugin's dashboard.
   *
   * @return bool Tru is current user has the permissions.
   */
  public function currentUserCanManage() {
    return current_user_can('manage_options');
  }

  /**
   * Executes a SQL query.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @uses InstapageCmsPluginDrupal7Connector::prepare() to change '%s' to '?'.
   *
   * @return bool True if the query is successful. DB error is logged and false if returned otherwise.
   */
  public function query($sql) {
    global $wpdb;

    $args = func_get_args();
    array_shift($args);
    $sql = $this->prepare($sql, $args);
    $result = $wpdb->query($sql);
    $this->checkLastQuery();

    return $result;
  }

  /**
   * Gets the last ID of an insert query.
   *
   * @return integer|boolean Last insert ID of false on error.
   */
  public function lastInsertId() {
    global $wpdb;

    return $wpdb->insert_id;
  }

  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @param string $sql SQL query. %s can be used to output pre-formatted values.
   *
   * @return string SQL query ready to execute in Drupal 8.
   */
  public function prepare($sql, $args = array()) {
    global $wpdb;

    if (isset($args[0]) && is_array($args[0])) {
      $args = $args[0];
    }

    if (count($args)) {
      return $wpdb->prepare($sql, $args);
    } else {
      return $sql;
    }
  }

  /**
   * Executes the query and returns the first row.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @return mixed first row of results of the query.
   */
  public function getRow($sql) {
    global $wpdb;

    $args = func_get_args();
    array_shift($args);
    $sql = $this->prepare($sql, $args);
    $result = $wpdb->get_row($sql, 'OBJECT');
    $this->checkLastQuery();

    return $result;
  }

  /**
   * Executes the query and returns a list of results.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @return mixed Array of results, false on error.
   */
  public function getResults($sql) {
    global $wpdb;

    $args = func_get_args();
    array_shift($args);
    $sql = $this->prepare($sql, $args);
    $result = $wpdb->get_results($sql, 'OBJECT');
    $this->checkLastQuery();

    return $result;
  }

  /**
   * Gets the DB prefix from CMS configuration.
   *
   * @return string DB prefix.
   */
  public function getDBPrefix() {
    global $wpdb;

    return $wpdb->prefix;
  }

  /**
   * Gets charset collation.
   *
   * @return string Database charset collation.
   */
  public function getCharsetCollate() {
    global $wpdb;

    return $wpdb->get_charset_collate();
  }

  /**
   * Performsremote request in a way specific for WodrPress.
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   * @param string $method Method of the request. 'POST' or 'GET'.
   *
   * @return array Request result in a form of associative array.
   */
  public function remoteRequest($url, $data, $headers = array(), $method = 'POST') {
    $body = is_array($data) ? $data : (array) $data;

    if ($method == 'POST' && (!is_array($body) || !count($body))) {
      $body = array('ping' => true);
      InstapageCmsPluginHelper::writeDiagnostics($body, 'Request (' . $method . ') data empty. Ping added.');
    }

    if ($method == 'GET' && is_array($data)) {
      $dataString = http_build_query($body, '', '&');
      $url .= '?' . urldecode($dataString);
      $body = null;
      InstapageCmsPluginHelper::writeDiagnostics($url, 'GET Request URL');
    }

    $cookies = @InstapageCmsPluginHelper::getVar($data['cookies'], array());

    $args = array(
      'method' => $method,
      'timeout' => 45,
      'redirection' => 5,
      'httpversion' => '1.0',
      'sslverify' => false,
      'blocking' => true,
      'headers' => $headers,
      'body' => $body,
      'cookies' => $cookies
   );

    switch($method) {
      case 'POST':
        $response = wp_remote_post($url, $args);
        break;

      case 'GET':
        $response = wp_remote_get($url, $args);
        break;

      default:
        $response = null;
    }

    if (is_wp_error($response)) {
      return (object) array('status' => 'ERROR', 'message' => $response->get_error_message());
    } else {
      return $this->prepareResponse($response);
    }
  }

  /**
   * Performs remote POST request.
   *
   * @uses InstapageCmsPluginWPConnector::remoteRequest().
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   *
   * @return array Request result in a form of associative array.
   */
  public function remotePost($url, $data, $headers = array()) {
    return $this->remoteRequest($url, $data, $headers, 'POST');
  }

  /**
   * Performs remote GET request.
   *
   * @uses InstapageCmsPluginWPConnector::remoteRequest().
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   *
   * @return array Request result in a form of associative array.
   */
  public function remoteGet($url, $data, $headers = array()) {
    return $this->remoteRequest($url, $data, $headers, 'GET');
  }

  /**
   * Gets the site base URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site base URL. With protocol or not.
   */
  public function getSiteURL($protocol = true) {
    $url = get_site_url();

    if (!$protocol) {
      $url = str_replace(array('http://', 'https://'), '', $url);
    }

    return $url;
  }

  /**
   * Gets the site home URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site home URL. With protocol or not.
   */
  public function getHomeURL($protocol = true) {
    $url = get_home_url();

    if (!$protocol) {
      $url = str_replace(array('http://', 'https://'), '', $url);
    }

    return $url;
  }

  /**
   * Gets the AJAX URL.
   *
   * @return string AJAX URL.
   */
  public function getAjaxURL() {
    return admin_url('admin-ajax.php') . '?action=instapage_ajax_call';
  }

  /**
   * Gets the value of language variable.
   */
  public function lang() {
    $arguments = func_get_arg(0);

    if (!count($arguments)) {
      return null;
    }

    $text = $arguments[0];
    $variables = array_slice($arguments, 1);

    if (!count($variables)) {
      return __($text);
    }

    return vsprintf(__($text), $variables);
  }


  /**
   * Initiates Instapage plugin's DB structure and loads plugin's classes.
   */
  public function initPlugin() {
    InstapageCmsPluginHelper::writeDiagnostics($_SERVER['REQUEST_URI'], 'Instapage plugin initiated. REQUEST_URI');

    if ($this->isInstapagePluginDashboard()) {
      add_action('admin_enqueue_scripts', array($this, 'addAdminJS'));
      add_action('admin_enqueue_scripts', array($this, 'addAdminCSS'));
    }

    add_action('admin_menu', array($this, 'addInstapageMenu'), 5);
    add_filter('plugin_action_links_' . plugin_basename(INSTAPAGE_PLUGIN_FILE), array($this, 'addActionLink'));
    add_action('wp_ajax_instapage_ajax_call', array($this, 'ajaxCallback'));
    add_action('wp_ajax_nopriv_instapage_ajax_call', array($this, 'ajaxCallback'));
    add_action('init', array($this, 'checkProxy'), 1);
    add_action('wp', array($this, 'checkHomepage'), 1);
    add_action('wp', array($this, 'checkCustomUrl'), 1);
    add_action('template_redirect', array($this, 'check404'), 1);
    register_uninstall_hook(INSTAPAGE_PLUGIN_FILE, array('InstapageCmsPluginWPConnector', 'removePlugin'));
    add_filter('https_ssl_verify', '__return_false');
  }

  /**
   * Removes the plugin.
   */
  public function removePlugin() {
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $db = InstapageCmsPluginDBModel::getInstance();
    $subaccount->disconnectAccountBoundSubaccounts(true);
    $db->removePluginTables();
  }

  /**
   * Adds a link to WP admin sidebar.
   */
  public function addInstapageMenu() {
    $iconSvg = InstapageCmsPluginHelper::getMenuIcon();

    add_menu_page(
      __('Instapage: General settings'),
      __('Instapage'),
      'manage_options',
      'instapage_dashboard',
      array($this, 'loadPluginDashboard'),
      $iconSvg,
      30
   );
  }

/**
 * WP filter. Adds a link to Instapage plugin's dashboard in WP plugin list.
 *
 * @param array $links List of links admin dashboard.
 */
  public function addActionLink($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=instapage_dashboard') . '">' . InstapageCmsPluginConnector::lang('Instapage dashboard') . '</a>';

    return $links;
  }

  /**
   * Adds JS necessary to for plugin's dashboard.
   */
  public function addAdminJS() {
    $jsDir = plugins_url('assets/js', INSTAPAGE_PLUGIN_FILE);
    $knockoutDir = plugins_url('knockout', INSTAPAGE_PLUGIN_FILE);
    $languageFile = plugins_url('assets/lang/' . InstapageCmsPluginConnector::getSelectedLanguage() . '.js', INSTAPAGE_PLUGIN_FILE);

    wp_register_script('instapage-dictionry', $languageFile, null, false, true);
    wp_register_script('instapage-lang', $jsDir . '/InstapageCmsPluginLang.js', null, false, true);
    wp_register_script('instapage-knokout', $knockoutDir . '/core/knockout-3.4.0.js', null, false, true);
    wp_register_script('instapage-knokout-no-conflict', $jsDir . '/knockout-no-conflict.js', null, false, true);
    wp_register_script('instapage-knokout-simple-grid', $knockoutDir . '/core/knockout.simpleGrid.3.0.js', null, false, true);
    wp_register_script('instapage-download', $jsDir . '/download.js', null, false, true);
    wp_register_script('instapage-ajax', $jsDir . '/InstapageCmsPluginAjax.js', null, false, true);
    wp_register_script('instapage-paged-grid-model', $knockoutDir . '/view_models/InstapageCmsPluginPagedGridModel.js', null, false, true);
    wp_register_script('instapage-edit-model', $knockoutDir . '/view_models/InstapageCmsPluginEditModel.js', null, false, true);
    wp_register_script('instapage-settings-model', $knockoutDir . '/view_models/InstapageCmsPluginSettingsModel.js', null, false, true);
    wp_register_script('instapage-messages-model', $knockoutDir . '/view_models/InstapageCmsPluginMessagesModel.js', null, false, true);
    wp_register_script('instapage-toolbar-model', $knockoutDir . '/view_models/InstapageCmsPluginToolbarModel.js', null, false, true);
    wp_register_script('instapage-master-model', $knockoutDir . '/view_models/InstapageCmsPluginMasterModel.js', null, false, true);

    wp_enqueue_script('instapage-dictionry');
    wp_enqueue_script('instapage-lang');
    wp_enqueue_script('instapage-knokout');
    wp_enqueue_script('instapage-knokout-no-conflict');
    wp_enqueue_script('instapage-knokout-simple-grid');
    wp_enqueue_script('instapage-ajax');
    wp_enqueue_script('instapage-download');
    wp_enqueue_script('instapage-paged-grid-model');
    wp_enqueue_script('instapage-edit-model');
    wp_enqueue_script('instapage-settings-model');
    wp_enqueue_script('instapage-messages-model');
    wp_enqueue_script('instapage-toolbar-model');
    wp_enqueue_script('instapage-master-model');

    // UI KIT.
    wp_register_script('instapage-mrwhite', $jsDir . '/mrwhite.js', null, false, true);
    wp_register_script('instapage-dropdowns', $jsDir . '/dropdowns.js', null, false, true);
    wp_register_script('instapage-expand-collapse', $jsDir . '/expand-collapse.js', null, false, true);
    wp_register_script('instapage-input', $jsDir . '/input.js', null, false, true);
    wp_register_script('instapage-jq-hoverintent', $jsDir . '/jq.hoverintent.js', null, false, true);
    wp_register_script('instapage-jquery-tmpl-min', $jsDir . '/jquery.tmpl.min.js', null, false, true);
    wp_register_script('instapage-ripple', $jsDir . '/ripple.js', null, false, true);
    wp_register_script('instapage-select2-min', $jsDir . '/select2.min.js', null, false, true);
    wp_register_script('instapage-snack-bars', $jsDir . '/snack-bars.js', null, false, true);
    wp_register_script('instapage-tabs', $jsDir . '/tabs.js', null, false, true);

    wp_enqueue_script('jquery');
    wp_enqueue_script('instapage-mrwhite');
    wp_enqueue_script('instapage-dropdowns');
    wp_enqueue_script('instapage-expand-collapse');
    wp_enqueue_script('instapage-input');
    wp_enqueue_script('instapage-jq-hoverintent');
    wp_enqueue_script('instapage-jquery-tmpl-min');
    wp_enqueue_script('instapage-ripple');
    wp_enqueue_script('instapage-select2-min');
    wp_enqueue_script('instapage-snack-bars');
    wp_enqueue_script('instapage-tabs');
  }

  /**
   * Adds necessary CSS for plugin's dashboard.
   */
  public function addAdminCSS() {
    $cssDir = plugins_url('assets/css', INSTAPAGE_PLUGIN_FILE);
    wp_enqueue_style('instapage-mrwhite-reset', $cssDir . '/mrwhite-reset.css');
    wp_enqueue_style('instapage-mrwhite-ui-kit', $cssDir . '/mrwhite-ui-kit.css');
    wp_enqueue_style('instapage-general', $cssDir . '/general.css');
  }

  /**
   * Loads the plugin dashboard.
   */
  public function loadPluginDashboard() {
    InstapageCmsPluginHelper::initAjaxURL();
    InstapageCmsPluginHelper::loadTemplate('messages');
    InstapageCmsPluginHelper::loadTemplate('toolbar');
    InstapageCmsPluginHelper::loadTemplate('base');
  }

  /**
   * Executes an action requested via AJAX.
   */
  public function ajaxCallback() {
    InstapageCmsPluginConnector::ajaxCallback();
  }

  /**
   * Checks if current URL is login page.
   *
   * @return bool True if current URL is login page.
   */
  public function isLoginPage() {
    $pagenow = InstapageCmsPluginHelper::getVar($GLOBALS['pagenowfff'], 'undefined');

    return in_array($pagenow, array('wp-login.php', 'wp-register.php'));
  }

  /**
   * Checks (and displays) if a landing page should be displayed instead of normal content served by CMS.
   *
   * @param string $type Type of page to check ('page', 'home' or '404').
   * @param string $slug Slug to check. Default: ''.
   */
  public function checkPage($type, $slug = '') {
    $page = InstapageCmsPluginPageModel::getInstance();
    $result = $page->check($type, $slug);
    $supportLegacy = InstapageCmsPluginHelper::getMetadata('supportLegacy', true);

    if (!$result && $supportLegacy && $this->legacyArePagesPresent()) {
      $result = $this->legacyGetPage($slug);
    }

    if (isset($result->instapage_id) && $result->instapage_id) {
      if ($type == '404') {
        $page->display($result, 404);
      } else {
        $page->display($result);
      }
    }
  }

  /**
   * Checks (and displays) if a landing page marked as homepage should be displayed instead of normal CMS homepage.
   *
   * @uses InstapageCmsPluginWPConnector::checkPage()
   */
  public function checkHomepage() {
    $homeUrl = str_replace(array('http://', 'https://'), '', rtrim($this->getHomeURL(), '/'));
    $homeUrlSegments = explode('/', $homeUrl);
    $uriSegments = explode('?', $_SERVER['REQUEST_URI']);
    $uriSegments = explode('/', rtrim($uriSegments[0], '/'));

    if (
      (count($uriSegments) !== count($homeUrlSegments)) ||
      (count($homeUrlSegments) > 1 && $homeUrlSegments[1] != $uriSegments[1])
   ) {
      return false;
    }

    $this->checkPage('home');

    return true;
  }

  /**
   * Checks (and displays) if a landing page marked as 404 should be displayed instead of normal CMS 404 page.
   *
   * @uses InstapageCmsPluginWPConnector::checkPage()
   */
  public function check404() {
    if (is_404()) {
      $this->checkPage('404');

      return true;
    }

    return false;
  }

  /**
   * Checks (and displays) if a landing page hould be displayed instead of normal CMS page under current URL.
   *
   * @uses InstapageCmsPluginWPConnector::checkPage()
   */
  public function checkCustomUrl() {
    $slug = InstapageCmsPluginHelper::extractSlug($this->getHomeURL());

    if ($slug) {
      $this->checkPage('page', $slug);
    }

    return true;
  }

  /**
   * Checks (and processes it) if a lcurrent request should be processes by plugin's proxy.
   */
  public function checkProxy() {
    $services = InstapageCmsPluginServicesModel::getInstance();

    if ($services->isServicesRequest()) {
      try {
        $services->processProxyServices();

        return;
      } catch (Exception $e) {
        echo $e->getMessage();
      }
    }
  }

  /**
   * get list of slugs that can't be used to publish a landing page.
   *
   * @return array List of prohibitted slugs.
   */
  public function getProhibitedSlugs() {
    $result = array_merge($this->getPostSlugs(), $this->getTermSlugs(), $this->getPageSlugs(), InstapageCmsPluginConnector::getLandingPageSlugs());
    return $result;
  }

  /**
   * Gets the HTML for CMS options.
   *
   * @return string HTML to include in the debug log.
   */
  public function getOptionsDebugHTML() {
    $necessaryOptions = array(
      'siteurl',
      'home',
      'permalink_structure',
      'blog_charset',
      'template',
      'db_version',
      'initial_db_version'
   );

    foreach ($necessaryOptions as $opt) {
      $options[$opt] = get_option($opt, 'n/a');
    }

    $view = InstapageCmsPluginViewModel::getInstance();
    $view->init(INSTAPAGE_PLUGIN_PATH .'/templates/log_options.php');
    $view->rows = $options;

    return $view->fetch();
  }

  /**
   * Gets the HTML for CMS plugins/modules.
   *
   * @return string HTML to include in the debug log.
   */
  public function getPluginsDebugHTML() {
    $allPlugins = get_plugins();
    $view = InstapageCmsPluginViewModel::getInstance();
    $view->init(INSTAPAGE_PLUGIN_PATH . '/templates/log_plugins.php');
    $view->rows = $allPlugins;

    return $view->fetch();
  }

  /**
   * Gets the sitename from CMS config.
   *
   * @return string Sitename.
   */
  public function getSitename($sanitized = false) {
    $sitename = get_bloginfo('name');

    return ($sanitized) ? sanitize_title($sitename) : $sitename;
  }

  /**
   * Sends an e-mail using CMS native email sending method.
   *
   * @param string $to Receiver address.
   * @param string $subject A subject.
   * @param string $message A message.
   * @param string $headers Message headers. Default: ''.
   * @param aray $attachments Attachments.
   *
   * @return bool True on success.
   */
  public function mail($to, $subject, $message, $headers = '', $attachments = array()) {
    return wp_mail($to, $subject, $message, $headers, $attachments);
  }

  /**
   * Gets the landing pages saved in legacy DB structure.
   *
   * @return array List of landing pages from legacy DB structure.
   */
  public function getDeprecatedData() {
    global $wpdb;

    $sql = "SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) WHERE ({$wpdb->posts}.post_type = %s) AND ({$wpdb->posts}.post_status = 'publish') AND ({$wpdb->postmeta}.meta_key IN ('instapage_my_selected_page', 'instapage_name', 'instapage_my_selected_page', 'instapage_slug'))";

    $rows = $this->getResults($sql, 'instapage_post');
    $posts = array();

    foreach ($rows as $row) {
      if (!array_key_exists($row->ID, $posts)) {
        $posts[$row->ID] = array();
      }

      $posts[$row->ID][$row->meta_key] = $row->meta_value;
    }

    $results = array();

    foreach ($posts as $post) {
      $pageObj = new stdClass;
      $pageObj->id = 0;
      $pageObj->landingPageId = $post['instapage_my_selected_page'];
      $pageObj->slug = $post['instapage_slug'];
      $pageObj->type = 'page';
      $pageObj->enterprise_url = $pageObj->slug ? InstapageCmsPluginConnector::getHomeURL() . '/' . $pageObj->slug : InstapageCmsPluginConnector::getHomeURL();
      $results[] = $pageObj;
    }

    $frontPageId = get_option('instapage_front_page_id', false);

    if ($frontPageId) {
      $pageObj = new stdClass;
      $pageObj->id = 0;
      $pageObj->landingPageId = $frontPageId;
      $pageObj->slug = '';
      $pageObj->type = 'home';
      $pageObj->enterprise_url = InstapageCmsPluginConnector::getHomeURL();
      $results[] = $pageObj;
    }

    $notFoundId = get_option('instapage_404_page_id', false);

    if ($notFoundId) {
      $page = InstapageCmsPluginPageModel::getInstance();
      $pageObj = new stdClass;
      $pageObj->id = 0;
      $pageObj->landingPageId = $notFoundId;
      $pageObj->slug = $page->getRandomSlug();
      $pageObj->type = '404';
      $pageObj->enterprise_url = InstapageCmsPluginConnector::getHomeURL() . '/' . $pageObj->slug;
      $results[] = $pageObj;
    }

    return $results;
  }

  /**
   * Properly escapes the HTML.
   *
   * @param string $html HTML to escape.
   *
   * @return string Escaped HTML.
   */
  public function escapeHTML($html) {
    return esc_html($html);
  }

  /**
   * Checks if any landing page is present in legacy DB structure.
   *
   * @return bool True if there are pages in legacy DB structure.
   */
  public function legacyArePagesPresent() {
    global $wpdb;

    $sql = "SELECT COUNT({$wpdb->posts}.ID) AS page_count FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_status = 'publish'";
    $row = $wpdb->get_row($wpdb->prepare($sql, 'instapage_post'));

    if (isset($row->page_count) && $row->page_count > 0) {
      return true;
    }

    return false;
  }

  /**
   * Checks if there is a need to replace content of CMS with a landing page. Prevents content replacement on admin/login pages.
   *
   * @return bool True if replace is possible.
   */
  public function isHtmlReplaceNecessary() {
    if (is_admin() || $this->isLoginPage() || InstapageCmsPluginHelper::isCustomParamPresent()) {
      InstapageCmsPluginHelper::writeDiagnostics('is_admin || isLoginPage || isCustomParamPresent', 'HTML Replace is not necessary');

      return false;
    }

    return true;
  }

  /**
   * Pulls the data from legacy DB structure.
   *
   * @param string $slug Slug of a page, that we want to pull.
   *
   * @return object Landing page object.
   */
  public function legacyGetPage($slug) {
    global $wpdb;
    $result = new stdClass;
    $result->slug = $slug;
    $result->enterprise_url = $slug ? InstapageCmsPluginConnector::getHomeURL() . '/' . $slug : InstapageCmsPluginConnector::getHomeURL();

    $sql = "SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) WHERE ({$wpdb->posts}.post_type = %s) AND ({$wpdb->posts}.post_status = 'publish') AND ({$wpdb->postmeta}.meta_key IN ('instapage_my_selected_page', 'instapage_name', 'instapage_my_selected_page', 'instapage_slug'))";

    $rows = $wpdb->get_results($wpdb->prepare($sql, 'instapage_post'));
    $posts = array();

    foreach ($rows as $row) {
      if (!array_key_exists($row->ID, $posts)) {
        $posts[$row->ID] = array();
      }

      $posts[$row->ID][$row->meta_key] = $row->meta_value;
    }

    foreach ($posts as $post) {
      if (isset($post['instapage_slug']) && $post['instapage_slug'] == $slug) {
        $result->instapage_id = isset($post['instapage_my_selected_page']) ? $post['instapage_my_selected_page'] : 0;

        return $result;
      }
    }

    $result->instapage_id = 0;

    return $result;
  }

  /**
   * Gets the settings module, a CMS-dependant part of the Settings page.
   *
   * @return string HTML form with settings for currently used CMS only.
   */
  public function getSettingsModule() {
    $html = '
    <div class="custom-params-form ui-section">
      <h3 class="ui-subtitle">' . InstapageCmsPluginConnector::lang('Support legacy pages') . '</h3>
      <p class="l-space-bottom-primary">' . InstapageCmsPluginConnector::lang('Instapage plugin will search for landing pages in old database structure (before 3.0 update). After successful migration this option should not be used.') . '</p>
      <label class="c-mark">
          <input class="c-mark__input" data-bind="checked: supportLegacy, click: autoSaveMetadata" type="checkbox" >
          <i class="c-mark__icon c-mark__icon--checkbox material-icons">check</i>
          <span class="c-mark__label">' . InstapageCmsPluginConnector::lang('Turn on legacy support.') . '</span>
        </label>
    </div>';

    return $html;
  }

  /**
   * Checks if last executed query resulted in an SQL error. Error is saved in the debug log.
   *
   * @return bool True if there was an SQL error.
   */
  private function checkLastQuery() {
    global $wpdb;
    $db = InstapageCmsPluginDBModel::getInstance();

    if (strpos($wpdb->last_query, $db->debugTable) === false && $wpdb->last_error !== '') {
      $messages = array(
        'Query: ' . $wpdb->last_query,
        'Error: ' . $wpdb->last_error
     );

      InstapageCmsPluginHelper::writeDiagnostics(implode("\n", $messages), 'DB Error');

      return false;
    }

    return true;
  }

  /**
   * Gets the list of slugs used by WP posts.
   *
   * @return array List of slugs used by posts.
   */
  private function getPostSlugs() {
    $postPrefix = '';
    $editUrl = $this->getSiteURL() . '/wp-admin/post.php?action=edit&post=';
    $dbPrefix = $this->getDBPrefix();
    $sql = 'SELECT ID AS id, CONCAT(\'' . $editUrl . '\', ID) AS editUrl FROM ' . $dbPrefix . 'posts WHERE post_type = \'post\' AND post_name <> \'\'';
    $results = $this->getResults($sql);

    if (is_array($results) && !empty($results)) {
      $siteUrl = get_home_url() . '/';

      foreach ($results as &$result) {
        $result->slug = trim(str_replace($siteUrl, '', get_permalink($result->id)), '/');
      }
    }

    return $results;
  }

  /**
   * Pulls slugs used by WordPress pages.
   *
   * @return array List of slugs used by pages.
   */
  private function getPageSlugs() {
    $editUrl = $this->getSiteURL() . '/wp-admin/post.php?action=edit&post=';
    $dbPrefix = $this->getDBPrefix();
    $sql = 'SELECT ID AS id, post_name AS slug, CONCAT(\'' . $editUrl . '\', ID) AS editUrl FROM ' . $dbPrefix . 'posts WHERE post_type = \'page\' AND post_name <> \'\' ';
    $results = $this->getResults($sql);

    return $results;
  }

  /**
   * Gets the list of slugs used by WP terms.
   *
   * @return array List of slugs used by terms.
   */
  private function getTermSlugs() {
    $editUrl1 = $this->getSiteURL() . '/wp-admin/edit-tags.php?action=edit&post_type=post&taxonomy=';
    $editUrl2 = '&tag_ID=';
    $dbPrefix = $this->getDBPrefix();
    $sql = 'SELECT t.term_id AS id, t.slug AS slug, CONCAT(\'' . $editUrl1 . '\', tt.taxonomy, \'' . $editUrl2 . '\', t.term_id) AS editUrl ' .
    'FROM ' . $dbPrefix . 'terms t LEFT JOIN ' . $dbPrefix . 'term_taxonomy tt ON t.term_id = tt.term_id ' .
    'WHERE (tt.taxonomy = \'category\' OR tt.taxonomy = \'post_tag\')' .
    'AND t.slug <> \'\'';
    $results = $this->getResults($sql);

    return $results;
  }

  /**
   * Checks if current URL is plugin's dashboard.
   *
   * @return boolean
   */
  private function isInstapagePluginDashboard() {
    if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'instapage_dashboard') {
      return true;
    }

    return false;
  }

  /**
   * Prepares the remote request response to unify response object in all integrated CMSes.
   *
   * @param object $request Request result.
   *
   * @return array Standard Instapage plugin request response array.
   */
  private function prepareResponse($request) {
    $headers = @InstapageCmsPluginHelper::getVar($request['headers'], null);

    if (is_object($headers) && get_class($headers) == 'Requests_Utility_CaseInsensitiveDictionary') {
      $headers = $headers->getAll();
    }

    $responseCode = @InstapageCmsPluginHelper::getVar($request['status_code'], 0);

    if (!$responseCode) {
      $responseCode = @InstapageCmsPluginHelper::getVar($request['response']['code'], 200);
    }

    $status = @InstapageCmsPluginHelper::getVar($request['status'], '');

    if (!$status) {
      $status = @InstapageCmsPluginHelper::getVar($request['response']['message'], '');
    }

    return array(
      'body' => @InstapageCmsPluginHelper::getVar($request['body'], ''),
      'status' => $status,
      'code' => $responseCode,
      'headers' => $headers
   );
  }
}
