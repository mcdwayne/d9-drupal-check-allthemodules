<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 1/31/16
 * Time: 8:41 PM
 */

namespace Drupal\forena;


use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Link;
use Drupal\Core\Url;

class AppService {
  protected static $instance;
  public $base_path;
  public $siteContext;
  public $input_format;
  public $default_skin;
  public $doc_formats;
  public $doc_defaults;

  /**
   * Singleton Factory
   * @return \Drupal\forena\AppService
   */
  public static function instance($force_new = FALSE) {
    if ((static::$instance === null) || $force_new) static::$instance = new static();
    return static::$instance;
  }

  public function alter($hook, &$var1, $var2=NULL) {
    \Drupal::moduleHandler()->alter('forena_parameters', $var1, $var2);
  }

  public function correctModulePath($module, &$path) {
    if (strpos($path, '/') !== 0 && strpos($path, '..') !== 0) {
      $tmp_path = drupal_get_path('module', $module) . "/" . $path;
      if (is_dir($tmp_path)) {
        $path = $tmp_path;
      }
    }
  }

  /**
   * Returns the list of Renderer plugins
   * @return array
   */
  public function getRendererPlugins() {
    $render_map = [];
    $pm = \Drupal::service('frxplugin.manager.renderer');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $renderer) {
      // Find out if plugin is defined.
      $id = $renderer['id'];
      $class = $renderer['class'];
      $render_map[$id] = $class;

    }
    return $render_map;
  }

  /**
   * Returns the list of ajax plugins.
   * @return array
   */
  public function getAjaxPlugins() {
    $plugin_map = [];
    $pm = \Drupal::service('frxplugin.manager.ajax');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $plugin) {
      // Find out if plugin is defined.
      $id = $plugin['id'];
      $class = $plugin['class'];
      $plugin_map[$id] = $class;

    }
    return $plugin_map;
  }

  /**
   * Returns the list of context plugins.
   * @return array
   */
  public function getContextPlugins() {
    $plugin_map = [];
    $pm = \Drupal::service('frxplugin.manager.context');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $plugin) {
      // Find out if plugin is defined.
      $id = $plugin['id'];
      $class = $plugin['class'];
      $plugin_map[$id] = $class;

    }
    return $plugin_map;
  }
  /**
   * Returns a list of formatters that provide format methods.
   * @return array
   */
  public function getFormatterPlugins() {
    $formatters = [];
    $pm = \Drupal::service('frxplugin.manager.formatter');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $formatter) {
      // Find out if plugin is defined.
      $class = $formatter['class'];
      $formatters[] = $class;

    }
    return $formatters;
  }

  /**
   * Use host application specificy to find the location of a library.
   */
  public function findLibrary($library) {
    $libraries = array(
      'dataTables' => 'dataTables/media/js/jquery.dataTables.min.js',
      'mpdf' => 'mpdf/mpdf.php',
      'SVGGraph' => 'SVGGraph/SVGGraph.php',
      'prince' => 'prince/prince.php'
    );
    $path = isset($libraries[$library]) && file_exists('libraries/' . $libraries[$library]) ? 'libraries/' . $libraries[$library] : '';
    return $path;
  }

  public function getDocumentPlugins() {
    $type_map = [];
    $pm = \Drupal::service('frxplugin.manager.document');
    $enabled_types = \Drupal::config('forena.settings')->get('doc_formats');
    $plugins = $pm->getDefinitions();
    foreach ($enabled_types as $doc_type) {
      // Find out if plugin is defined.
      if (isset($plugins[$doc_type])) {
        $def = $plugins[$doc_type];
        $ext = $def['ext'];
        $class = $def['class'];
        $type_map[$ext] = $class;
      }
    }
    return $type_map;
  }

  public function getDriverPlugins() {
    $driver_plugins = [];
    /** @var FrxDriverPluginManager $pm */
    $pm = \Drupal::service('frxplugin.manager.driver');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $plugin) {
      $id = $plugin['id'];
      $class = $plugin['class'];
      $driver_plugins[$id] = $class;
    }
    return $driver_plugins;
  }


  public function getForenaProviders() {
    $discovery = new YamlDiscovery('forena', \Drupal::moduleHandler()->getModuleDirectories());
    $providers = $discovery->findAll();
    foreach ($providers as $module_name =>  $provider) {
      // Adjust Report Directories based on module name
      if (isset($provider['report directory'])) {
        $this->correctModulePath($module_name, $providers[$module_name]['report directory']);
      }

      if (isset($provider['data'])) {
        foreach($provider['data'] as $data_provider => $definition) {
          $this->correctModulePath(
            $module_name,
            $providers[$module_name]['data'][$data_provider]['source']
          );
        }
      }
    }
    \Drupal::moduleHandler()->alter('forena_providers', $providers);
    return $providers;
  }

  /**
   * Return Current site context.
   * @return array
   */
  public function __construct() {
    $site = array();
    global $language;
    global $user;
    global $theme_path;
    global $base_root;
    $site['base_path'] = $this->base_path =  base_path();
    $site['dir'] = rtrim(base_path(), '/');
    $site['theme_path'] = base_path() . $theme_path;
    $site['theme_dir'] = &$theme_path;
    $site['base_url'] = &$base_root;
    $user = \Drupal::currentUser();
    $site['user_name'] = $user->id() ? $user->getAccountName() : '';
    $site['uid'] = $user->id();
    $site['language'] = &$language;
    //@TODO: Current Page
    //$site['page'] = base_path() . $_GET['q'];
    $dest = drupal_get_destination();
    $site['destination'] = $dest['destination'];
    $this->siteContext = $site;
    $config = \Drupal::config('forena.settings');
    $this->input_format = $config->get('input_format');
    $this->default_skin = $config->get('default_skin');
    $this->doc_formats = $config->get('doc_formats');
    $this->doc_defaults = $config->get('doc_defaults');

  }

  public function currentPath() {
    $path = \Drupal::service('path.current')->getPath();
    return $path;
  }

  /**
   * Access test
   * @param $right
   * @return mixed
   */
  public function access($right) {
    return \Drupal::currentUser()->hasPermission($right);
  }

  /**
   * Determine data directory.
   * @return string
   *   Data Directory for overridden 
   */
  public function dataDirectory() {
    // Determine writeable directory for data.
    $path = \Drupal::config('forena_query.settings')->get('data_path');
    if (!$path) {
      $path = drupal_realpath("private://data/source");
      if ($path) {
        if (!file_exists($path)) @mkdir($path, null, TRUE);
      }
    }
    return $path; 
  }

  /**
   * @param array $parmaters
   *   Parameter used to buidl the form.
   * @return array
   */
  public function buildParametersForm($parameters) {
    if ($parameters) {
      return \Drupal::formBuilder()->getForm('\Drupal\forena\Form\ParameterForm', $parameters);
    }
    else {
      return [];
    }
  }

  /**
   * General wrapper procedure for reporting erros
   *
   * @param string $short_message Message that will be displayed to the users
   * @param string $log Message that will be recorded in the logs.
   */
  public function error($short_message='', $log='') {
    if ($short_message) {
      drupal_set_message($short_message, 'error', FALSE);
    }
    if ($log) {
      \Drupal::logger('forena')->error($log);
    }
  }

  /**
   * Debug handler
   * Enter description here ...
   * @param string $short_message
   * @param string $log
   */
  public function debug($short_message='', $log='') {
    if ($log) {
      \Drupal::logger('forena')->notice($log, []);
    }
    if ($short_message) {
      drupal_set_message($short_message);
    }
  }

  /**
   * @param $elements
   * @return mixed
   */
  public function drupalRender(&$elements) {
    return \Drupal::service('renderer')->render($elements);
  }

  /**
   *
   * @param $text
   * @param $fields
   * @return mixed
   */
  public function reportLink($text, $fields) {
    //@TODO:  Map the fields from teh report call to the options for URL.
    $link ='';
    $target = '';
    $class = '';
    $rel = '';
    $add_query = '';
    // Extract the above variables from the field definition
    extract($fields);

    $attributes = [];

    // Get data attributes from the field definition
    foreach($fields as $k => $v) {
      if (strpos($k, 'data-') === 0) {
        $attributes[$k] = $v;
      }
    }

    // use the target attribute to open links in new tabs or as popups.
    if (@strpos(strtolower($target), 'popup')===0) {
      $options = "status=1";
      $attributes = array('onclick' =>
        'window.open(this.href, \'' . $target . '\', "' . $options . '"); return false;');
    }
    else {
      if ($target) $attributes['target'] = $target;
    }

    // Rel
    if ($rel) $attributes['rel'] = $rel;
    // Class
    if ($class) $attributes['class'] = explode(' ', $class);

    // @TODO: Add libararies for modals.

    @list($path, $query) = explode('?', $link);
    @list($query, $queryFrag) = explode('#', $query);
    @list($path, $fragment) = explode('#', $path);
    $fragment = $fragment . $queryFrag;
    $data = array();
    parse_str($query, $data);

    // Add items from query string if specified.
    if ($add_query) {
      $parms = $_GET;
      $data = array_merge($parms, $data);
    }

    if (trim($path)) {
      // Work with internal links.
      if (!strpos($path, '://')) {
        $path = "/$path";
        $url = Url::fromUserInput(
          $path,
          [
            'fragment' => $fragment,
            'query' => $data,
            'attributes' => $attributes,
            'absolute' => TRUE
          ]
        );
      }
      else {
        $url = Url::fromUri(
          $path,
          [
            'fragment' => $fragment,
            'query' => $data,
            'attributes' => $attributes,
            'absolute' => TRUE
          ]
          );
      }

      $link = Link::fromTextAndUrl($text, $url)->toRenderable();
      $link_html = \Drupal::service('renderer')->render($link);
    }
    return $link_html;
  }
  
  public function url($path, $options) {
    if (strpos($path,'/')!== 0 && strpos($path, 'http' == FALSE)) {
      $path  = "/$path"; 
    }
    return Url::fromUserInput($path, $options)->toString(); 
  }

  /**
   * Render an application menu based on provided id and max depth.
   *
   * @param $menu_id
   * @param $max_depth
   * @return mixed
   */
  public function renderMenu($menu_id, $options=[]) {
    $menu_tree_service = \Drupal::service('menu.link_tree');
    $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
    $tree = $menu_tree_service->load($menu_id, $menu_parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree_service->transform($tree, $manipulators);
    $content = $menu_tree_service->build($tree);
    return (string) \Drupal::service('renderer')->render($content);
  }

}