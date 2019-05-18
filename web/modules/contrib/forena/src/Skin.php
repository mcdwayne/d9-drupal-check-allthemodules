<?php
/**
 * @file
 * Implements \Drupal\forena\Skins
 */
namespace Drupal\forena;
use Drupal\forena\AppService;
use Drupal\forena\File\ReportFileSystem;
use Drupal\forena\Token\ReportReplacer;
use Symfony\Component\Yaml\Parser;


/**
 * Class Skin
 *  A skin is a collectio of css and js files that need to get used by
 * an application or reports.  Skins are idntified by .fri files contained
 * in the report directory.
 *
 * Skins can specify external JS Libraries as well as
 *
 * Used to be called a "form"
 */
class Skin {
  use FrxAPI;
  static protected $skins = [];
  public $name = '';
  public $library = [];
  public $stylesheets = [];
  public $scripts = [];
  public $dependencies = [];
  public $default_skin = '';
  public $info = [];
  protected $replacer;


  /**
   * Ojbect factory for Skins.
   * @param string $skin
   *   Name of skin to be loaded
   * @return \Drupal\forena\Skin
   *   Skin object factory. 
   */
  static public function instance($skin) {
    if ($skin && !isset(static::$skins[$skin])) {
      static::$skins[$skin] = new Skin($skin);
    }
    return static::$skins[$skin];
  }

  /**
   * @param string $skin
   *   Load the skin
   */
  public function __construct($skin) {
    $this->replacer = new ReportReplacer();
    $this->load($skin);
  }

  /**
   * Add CSS Files
   * @param $type
   * @param $sheet
   */
  public function addCSS($type, $sheet) {
    if (strpos($sheet, 'http:') === 0 || strpos($sheet, 'https:') === 0) {
      $this->stylesheets[$type][] = $sheet;
    }
    elseif (ReportFileSystem::instance()->exists($sheet)) {
      $this->stylesheets[$type][] = ReportFileSystem::instance()->path($sheet);
    }
    elseif (file_exists($sheet)) {
      $this->stylesheets[$type][] = $sheet;
    }
  }

  /**
   * Return Replaced skin definition.
   * @return array
   *   The token replaced skin definition
   */
  public function replacedInfo() {
    $info = $this->info;
    $this->replacer->replaceNested($info);
    return $info;
  }

  /**
   * @param string $script
   *   The filename of the script to be added.
   */
  public function addJS($script) {
    if (strpos($script, 'http:') === 0 || strpos($script, 'https:') === 0) {
      $this->scripts[] = $script;
    }
    elseif ($this->reportFileSystem()->exists($script)) {
      $this->scripts[] = $this->reportFileSystem()->path($script);
    }
    elseif (file_exists('sites/all/libraries/' . $script)) {
      $this->scripts[] = 'sites/all/libraries/' . $script;
    }
    elseif (file_exists($script)) {
      $this->scripts[] = $script;
    }
  }

  /**
   * Load the skin based on the name.
   * @param string $name
   *   path/name of skin.
   * @return \Drupal\forena\Skin
   */
  public function load($name) {
    $fileSystem = $this->reportFileSystem();
    $path_info = [];
    if (!$name) $name = $this->default_skin;
    if ($name) {
      //Check for an info file
      $this->info = [];
      if ($fileSystem->exists($name . '.skin.yml')) {
        $this->info = Skin::parseYml($fileSystem->contents($name . '.skin.yml'));
        $path_info = $fileSystem->pathinfo($name . '.skin.yml');
      }
      // add and process sytlesheets
      if ($this->info) {
        $this->info['dir'] = '/' . $path_info['dirname'];
        $this->dataService()->setContext('skin', $this->info);
      }
    }

    // Replace tokens in css files based on paths.
    $this->library = [];
    if (isset($this->info['library'])) {
      $library = $this->info['library'];
      $new_library = $library;
      // Process CSS
      if (isset($library['css'])) {
        unset($new_library['css']);
        foreach ($library['css'] as $level => $files) {
          $new_library[$level] = [];
          foreach($files as $file => $options) {
            $new_file = $this->replacer->replace($file, TRUE); 
            $new_library['css'][$level][$new_file] = $options;
          }
        }
      }

      // Process JS
      if (isset($library['js'])) {
        unset($new_library['js']);
        foreach($library['js'] as $file => $options) {
          $new_file = $this->replacer->replace($file, TRUE);
          $new_library['js'][$new_file] = $options;
        }
      }
      $this->library = $new_library;
    }
    return $this;
  }

  /**
   * Adds on report specific skin files to
   * @param string $name
   *   name of report to add.
   */
  public function loadSkinFiles($name) {
    $this->addCSS('all', $name . '.css');
    foreach (AppService::instance()->doc_formats  as $ext) {
      $this->addCSS($ext, $name . '-' . $ext . '.css');
    }

    $this->addJS($name . '.js');
  }

  /**
   * @param string $data Data to be parsed.
   * @return array 
   *   Parsed YML data. 
   */
  static public function parseYml($data) {
    $parser = new Parser();
    return $parser->parse($data);
  }

  /**
   * @param string $data Data to be parsed
   */
  static public function parseJSON($data) {
    $parsed =  json_decode($data, TRUE);
    return $parsed;
  }

  /**
   * Merge definitions
   * @param array $definition
   *   Skein definition to be merged.
   */
  public function merge($definition) {
    if ($definition) foreach($definition as $key => $value) {
      if (isset($this->info[$key])) {
        $this->info[$key] = array_merge($this->info[$key], $value);
      }
      else {
        $this->info[$key] = $value;
      }
    }
  }

}