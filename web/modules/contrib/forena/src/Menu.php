<?php
/**
 * @file Menu.inc
 * Drupal menu builder
 * @author davidmetzler
 *
 */
namespace Drupal\forena;
use Drupal\forena\FrxAPI;
class Menu {
  private static $instance; //
  public $doc_defaults;  // Default Dcoument formats to inlcude
  public $doc_formats;  // Supported document formats
  public $name; // Report name wihout frx extention
  public $language; // Language
  public $format;  //format of report
  public $filename; //Name of file;
  public $ext; // Extention of file to be returned.
  public $directory; // name of directory;
  public $link;   //Link to report in current language
  public $i_Link; //Link to language chaning report name
  private $teng;

  /**
   * Singleton Factory
   * @return \Drupal\forena\Menu
   */
  public static function instance() {
    if (static::$instance === NULL) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  public function __construct() {

    $docs = \Drupal::config('forena.settings')->get('doc_defaults');

    // Load settings array into normal array
    if ($docs) foreach ($docs as $doc => $enabled) {
      if ($enabled) $this->doc_defaults[] = $doc;
    }
    else {
      $this->doc_formats = array('web');
    }

    // Load settings array into normal array

    $docs = \Drupal::config('forena.settings')->get('doc_formats');
    if ($docs) foreach ($docs as $doc => $enabled) {
      if ($enabled) $this->doc_formats[] = $doc;
    }

  }

  /**
   * Convert url into file paths based report name.  Load all link data for the
   * reprot.  Most report urls look like a java lassname, so urls are of the form
   * lang.subdir.anothersubdir.report.doctype.  This function parses the url
   * into it's components and store them in the menu object so that we can use
   * this name.
   * @param string $url
   *   path style name
   * @return array
   *   Descriptioin of the url. 
   */
  public function parseURL($url) {
    global $language;
    $name = $url;
    $tlang = '';
    // Determine if the report has an extention that is one of the docuemnt types
    $p = pathinfo($url);
    if (isset($p['extension']) && array_search($p['extension'], $this->doc_formats)!==FALSE) {
      $name = $p['dirname'] == '.' ? $p['filename'] : $p['dirname'] . '/' . $p['filename'];
      $format = $p['extension'];
    }
    else {
      $format = 'web';
      $ext = '';
    }
    // Convert class names to directory names.
    $base_name = str_replace('.', '/', $name);
    $name = $base_name;
    $i_name = $base_name;

    // Determine lanugage from url or drupal language interface.
    $lang = $language->language;
    if (\Drupal::moduleHandler()->moduleExists('locale')) {

      //First check to see if the report allready has a language in it
      @list($tlang,  $tbase_name) = explode('/', $base_name, 2);
      // FInd out if the starting name of the report is an installed language.
      $lang_list = locale_translatable_language_list();
      if (array_key_exists($tlang, $lang_list )) {
        $base_name = $tbase_name;
        if ($lang != $tlang) {
          $lang = $tlang;
          $language = $lang_list[$lang];
          $i_name = $tlang . '/' . $base_name;
        }
        if ($tlang == 'en') {
          $name = $base_name = $tbase_name;
        }
      }
      else {
        if ($lang != 'en') {
          $def_language = language_default('language');
          if (FrxAPI::File()->exists("$lang/$name.frx")) {
            $name = "$lang/$name";
          }
          elseif($def_language != 'en' && FrxAPI::File()->exists("$def_language/$name.frx")) {
            $name = "$def_language/$name";
            $lang = $def_language;
          }
        }
      }
    }
    //$name = trim(str_replace('.', '/', $base_name), '/');
    $filename = $name . '.frx';


    $desc['name'] = $this->name = $name;
    $desc['directory'] = FrxAPI::File()->directory($filename);
    $desc['filename'] = $filename;
    $desc['base_name'] = $this->base_name = $base_name;
    $desc['exists'] = FrxAPI::File()->exists($filename);
    $desc['link'] = $this->link = 'reports/' . str_replace('/' , '.', $name);
    $desc['i_link']= 'reports/' . $this->i_link = str_replace('/', '.', $i_name);
    $desc['language'] = $this->language = $lang;
    $desc['format'] = $this->format = $format;
    return $desc;
  }

  /**
   * Generate dcoument links based on report name.
   * @param array $docs
   */
  public function doclinks($docs = array()) {
    // Default documents.
    if (!$docs) {
      $docs = $this->doc_defaults;
    }
  }

  /**
   * Extract tokens from path
   * @param $path string path with FrxAPI Tokens in them
   */
  public function tokens($path) {
    return $this->teng->tokens($path);
  }


  /**
   * Add menu items to the items array
   * @param $items array of menu items.
   */
  public function addMenuItems(&$items) {
    GLOBAL $language;

    $result = FrxAPI::File()->menuReports();
    $reports = array();
    foreach ($result AS $row) {
      $access = TRUE;
      $cache = $row->cache;
      if ($cache) {
        // Load menu item defaults
        $menu = @$cache['menu'];
        $path = $menu['path'];
        $path_args = @$menu['args'];
        $type = @$menu['type'];
        $title = isset($menu['title']) ? $menu['title']:  $row->cache['title'];
        $weight = @$menu['weight'];
        $parent_path = '';

        //Default type
        switch ($type) {
          case 'normal-item':
            $menu_type = MENU_NORMAL_ITEM;
            break;
          case 'default-local-task':
            $menu_type = MENU_DEFAULT_LOCAL_TASK;
            break;
          case 'local-task':
            $menu_type = MENU_LOCAL_TASK;
            break;
          default:
            $menu_type = MENU_CALLBACK;
        }

        //Replace the tokens with drupal menu wildcards
        $tokens = $this->tokens($path);
        $new_path = $path;
        foreach ($tokens as $i => $token) {
          $new_path = str_replace(':' . $token, '%', $new_path);
          $args[] = $i;
        }
        // Now generate the callback arguments
        $parts = explode( '/', $new_path);
        $page_args = array_keys($parts, '%');
        $path_args = $path_args ? rtrim($path,'/') . '/' . ltrim($path_args, '/') : $path;
        $page_args = array_merge(array($path_args, $row->name), $page_args);

        // Set the access callback
        $access_callback = isset($cache['access']) ? 'forena_check_all_access' : TRUE;

        if ($menu_type == MENU_DEFAULT_LOCAL_TASK) {
          $parts = explode('/', $new_path);
          array_pop($parts);
          $parent_path = implode('/', $parts);
          // build the parent menu because we are also building the local task
          // but onlu do so if another report doesn't define the parent.

          if (!isset($items[$parent_path])) {
            $items[$parent_path] = array(
             'type' => MENU_CALLBACK,
             'title' => $row->cache['title'],
             'access callback' => $access_callback,
             'access arguments' => array($cache['access']),
             'page callback' => 'forena_report_menu_callback',
             'page arguments' => $page_args,
            );
            if (isset($row->cache['menu']['menu_name'])) $items['parent_path']['menu_name'] = $row->cache['menu']['menu_name'];
            if (isset($row->cache['menu']['tab_parent'])) $items['parent_path']['tab_parent'] = $row->cache['menu']['tab_parent'];
            if (isset($row->cache['menu']['tab_root'])) $items['parent_path']['tab_root'] = $row->cache['menu']['tab_root'];
            if (isset($row->cache['menu']['weight'])) $items['parent_path']['weight'] = $row->cache['menu']['weight'];
            if (isset($row->cache['menu']['weight'])) $items['parent_path']['weight'] = $row->cache['menu']['weight'];
            if (\Drupal::moduleHandler()->moduleExists('locale')) {
              $items[$parent_path]['title callback'] = 'forena_report_title_callback';
              $items[$parent_path]['title arguments'] = array($row->name, FALSE);
            }
            if ($access_callback === 'forena_check_all_access') $items[$parent_path]['access arguments'][] = $cache['access'];
          }
        }

        $items[$new_path] = array(
          'type' => $menu_type,
          'title' => $title,
          'access callback' => $access_callback,
          'access arguments' => array(@$cache['access']),
          'page callback' => 'forena_report_menu_callback',
          'page arguments' => $page_args,
        );
        //if ($parent) $items[$new_path]['parent'] = $parent;
        if (isset($menu['weight'])) $items[$new_path]['weight'] = $menu['weight'];
        if (!$parent_path) {
          if (isset($menu['menu_name'])) $items[$new_path]['menu_name'] = $menu['menu_name'];
          if (isset($menu['tab_parent'])) $items[$new_path]['tab_parent'] = $menu['tab_parent'];
          if (isset($menu['tab_root'])) $items[$new_path]['tab_root'] = $menu['tab_root'];
          if (isset($menu['plid'])) $items[$new_path]['plid'] = (int)$menu['plid'];
        }
        if (\Drupal::moduleHandler()->moduleExists('locale')) {
          $items[$new_path]['title callback'] = 'forena_report_title_callback';
          $items[$new_path]['title arguments'] = array($row->name, TRUE);
        }
        if ($access_callback === 'forena_check_all_access') $items[$new_path]['access arguments'][] = $cache['access'];
      }
    }

  }


}