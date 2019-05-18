<?php
namespace Drupal\forena\File;
use Drupal\forena\AppService;
use Drupal\forena\DataManager;
use Drupal\forena\Report;
use Drupal\forena\Skin;

class ReportFileSystem extends FileSystemBase {

  const CACHE_KEY = 'forena_report_file_system';

  protected static $instance;

  private $report_cache = array();
  public $language = 'en';
  public $default_language = 'en';

  /**
   * Singleton Factory
   * @return \Drupal\forena\File\ReportFileSystem
   */
  public static function instance($force_new = FALSE) {
    if ((static::$instance === NULL) || $force_new) static::$instance = new static();
    return static::$instance;
  }

  /**
   * Constructor
   *   Sets the initial reort directory
   */
  public function __construct() {
    parent::__construct();
    //@TODO: Find out drupal languages
    //$this->language = $language->language;
    //$this->language = language_default();
    // Load default directory from configuration.
    $report_path = \Drupal::config('forena.settings')->get('report_repos');
    if (!$report_path) {
      // @TODO: determine default file configuration.

      $report_path = \Drupal::service('file_system')->realpath('public://') . '/reports';
      if (!file_exists($report_path)) {
        @mkdir($report_path, 0777, TRUE);
      }
    }
    $default_directory = rtrim($report_path, '/');
    $this->dir = $default_directory;
    // Load directories from module.forena.yml files
    $providers = AppService::instance()->getForenaProviders();
    $directories = [];
    foreach ($providers as $module_name => $provider) {
      if (isset($provider['report directory'])) {
        $directories[] = $provider['report directory'];
      }
    }

    // Add directories form module hooks.
    $directories += \Drupal::moduleHandler()->invokeAll('forena_report_directory');
    foreach ($directories as $dir) {
      $this->includes[] = rtrim($dir, '/');
    }
  }

  /**
   * List all the reports for a language.
   * @return array
   *   array containing all reports. 
   */
  public function allReports() {
    $this->scan();
    $reports = array();
    $data = $this->allMetadataForExt('frx');
    $def_language = $this->default_language;
    if ($data) foreach ($data as $base_name => $obj) {
        if ($obj->metaData) {
          if ($obj->metaData['language'] != 'en') {
            $rpt_name = substr($base_name, strlen($obj->metaData['language']) +1);
          }
          else {
            $rpt_name = $base_name;
          }
          if($obj->metaData['language'] == $this->language) {
            $reports[$rpt_name] = $obj;
          }
          elseif ($obj->metaData['language'] == $def_language
              && (!isset($reports[$rpt_name]) || $reports[$rpt_name]->metaData['language'] == 'en')) {
            $reports[$rpt_name] = $obj;
          } elseif ($obj->metaData['language'] == 'en' && !isset($reports[$rpt_name])) {
            $reports[$rpt_name] = $obj;
        }
      }
    }
    uasort($reports, [$this, 'reportCompare']);
    return $reports;
  }

  /**
   * Sort compare function for sorting data by category then title.
   * @param object $a
   *   Report metatdata
   * @param object $b
   *   Report metadata
   * @return number
   */
  public function reportCompare($a, $b) {
    $c = strnatcasecmp($a->metaData['category'], $b->metaData['category']);
    if (!$c) {
      $c = strnatcasecmp($a->metaData['title'], $b->metaData['title']);
    }
    return $c;
  }

  public function reportTitleCompare($a, $b) {
    $c = strnatcasecmp($a['title'], $b['title']);
    return $c;
  }
  /**
   * Get the cached information for a single report.
   * @param string $name
   * @return object
   */
  public function getReportCacheInfo($name) {
    GLOBAL $language;
    $this->validateAllCache();
    $data = $this->getCache('frx');
    if ($language->language != 'en') {
      $lang = $language->language;
      $name = "$lang/$name";
    }
    return @$data[$name];
  }

  public function menuReports() {
    GLOBAL $language;
    $this->validateAllCache();
    $data = $this->getCache('frx');

    $reports = array();
    foreach ($data as $base_name => $obj) {
      if ($obj->cache && isset($obj->cache['menu']['path'])
        && (
            ($obj->cache['language'] == $language->language)
        || ($obj->cache['language'] == 'en' && !isset($obj->cache['menu']['path'])
        ))) {
        if ($obj->cache['language'] != 'en') {
          $obj->name = substr($base_name, 3);
        }
        else {
          $obj->name = $base_name;
        }
        $reports[$obj->cache['menu']['path']] = $obj;
      }
    }
    return $reports;
  }


  /**
   * Generate an ordered  list of reports by category
   * @param $categories
   * @return array Categories
   */
  public function reportsByCategory($categories = array()) {
    $this->scan();
    $data = $this->allReports();
    $reports = array();
    if ($data) foreach ($data as $base_name => $obj) {
      if ($obj->metaData && @$obj->metaData['category'] && empty($obj->metaData['hidden']) ) {
        $cache = $obj->metaData;
        if (!$categories || array_search($cache['category'], $categories)!==FALSE) {
          // Check each callback function to see if we have an error.

          $access = TRUE;
          if (@$cache['access']) {
            $access = FALSE;
            foreach ($cache['access'] as $provider => $rights) {
              $m = DataManager::instance()->repository($provider);
              foreach ($rights as $right) {
                if ($m->access($right)) {
                  $access = TRUE;
                };
              }
            }
          }
          if ($access) {
            $reports[$cache['category']][] = array(
                'title' => $cache['title'],
                'category' => $cache['category'],
                'report_name' => $base_name,
            );
          }

        }
      }
    }
    $sort = defined('SORT_NATURAL') ? SORT_NATURAL : SORT_REGULAR;
    // Sort the reports
    if ($reports) foreach ($reports as $category => $list) {
      uasort($reports[$category], [$this, 'reportTitleCompare']);
    }
    ksort($reports, $sort);


    return $reports;
  }

  public function skins() {
    $skins = array();
    $this->scan();

    // First find YML files
    $files = $this->allMetadataForExt('skin.yml');
    foreach ($files as $name => $obj) {
      $skins[$name] = isset($obj->metaData['name']) ? $obj->metaData['name'] : $name;
    }
    return $skins;
  }

  // Abstract drupal function
  public function localeEnabled() {
    return \Drupal::moduleHandler()->moduleExists('locale');
  }

  /**
   * @param $html
   * @return Report
   *   Report object created. 
   * Get cache data from the report.
   */
  public function createReport(&$html) {
    // Load the report
    $r = @new Report($html);
    return $r;
  }

  /**
   * Should load cache data based on that.
   * @see FrxFile::buildCache()
   */
  public function extractMetaData(&$object) {
    switch ($object->ext) {
    	case 'frx':
        $r_xml =file_get_contents($object->file);
        $r = $this->createReport($r_xml);

        // Save language info
        $lang = 'en';
        if ($this->localeEnabled()) {
          @list($tlang, $tname) = explode('/', $object->basename, 2);
          if (array_key_exists($tlang, locale_translatable_language_list())) {
            $lang = $tlang;
          }
        }

        // Get the security caches from the reports
        $cache = [];
        if ($r->rpt_xml) {
          $cache['title'] = $r->title;
          $cache['language'] = $lang;
          $cache['category'] = $r->category;
          $cache['hidden'] = @$r->options['hidden'];
          $cache['access'] = $r->access;
        }
        $object->metaData = $cache;
        if ($r) $r->__destruct();
        unset($r);
    	  break;
      case 'skin.yml':
        $object->metaData = Skin::parseYml(file_get_contents($object->file));
        break;
    }
  }

}
