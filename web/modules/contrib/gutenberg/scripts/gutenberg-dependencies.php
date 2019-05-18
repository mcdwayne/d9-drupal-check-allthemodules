<?php
// use Drupal\Core\CoreServiceProvider;
use Symfony\Component\Yaml\Yaml;    

if (PHP_SAPI !== 'cli') {
  return;
}

/**
 * 
 */
function getRootDir() {
  $dirs = explode(DIRECTORY_SEPARATOR, __DIR__);

  $root_dir = [];
  foreach ($dirs as $key => $value) {
    if ($value === 'modules') {
      return implode(DIRECTORY_SEPARATOR, $root_dir);
    }
    $root_dir[] = $value;
  }
}

$autoloader = require_once getRootDir() . '/autoload.php';

class scanDir {
  static private $directories, $files, $ext_filter, $recursive;

  static public function scan(){
    // Initialize defaults
    self::$recursive = false;
    self::$directories = array();
    self::$files = array();
    self::$ext_filter = false;

    // Check we have minimum parameters
    if(!$args = func_get_args()){
        die("Must provide a path string or array of path strings");
    }
    if(gettype($args[0]) != "string" && gettype($args[0]) != "array"){
        die("Must provide a path string or array of path strings");
    }

    // Check if recursive scan | default action: no sub-directories
    if(isset($args[2]) && $args[2] == true){self::$recursive = true;}

    // Was a filter on file extensions included? | default action: return all file types
    if(isset($args[1])){
        if(gettype($args[1]) == "array"){self::$ext_filter = array_map('strtolower', $args[1]);}
        else
        if(gettype($args[1]) == "string"){self::$ext_filter[] = strtolower($args[1]);}
    }

    // Grab path(s)
    self::verifyPaths($args[0]);
    return array_map(
      function($entry) {
        return substr($entry, 3, strlen($entry) - 1);
      },
      self::$files
    );
  }

  static private function verifyPaths($paths){
    $path_errors = array();
    if(gettype($paths) == "string"){$paths = array($paths);}

    foreach($paths as $path){
      if(is_dir($path)){
        self::$directories[] = $path;
        $dirContents = self::find_contents($path);
      } else {
        $path_errors[] = $path;
      }
    }

    if($path_errors){echo "The following directories do not exists<br />";die(var_dump($path_errors));}
  }

  // This is how we scan directories
  static private function find_contents($dir){
    $result = array();
    $root = scandir($dir);
    foreach($root as $value){
      if($value === '.' || $value === '..') {continue;}
      if(is_file($dir.DIRECTORY_SEPARATOR.$value)){
        if(!self::$ext_filter || in_array(strtolower(pathinfo($dir.DIRECTORY_SEPARATOR.$value, PATHINFO_EXTENSION)), self::$ext_filter)){
          self::$files[] = $result[] = $dir.DIRECTORY_SEPARATOR.$value;
        }
        continue;
      }
      if(self::$recursive){
        foreach(self::find_contents($dir.DIRECTORY_SEPARATOR.$value) as $value) {
          self::$files[] = $result[] = $value;
        }
      }
    }
    // Return required for recursive search
    return $result;
  }
}

$yaml = Yaml::parse(file_get_contents('../gutenberg.libraries.yml'));
$files = scandir('../vendor/gutenberg');
$total = count($files);

$packages = [];

foreach ($files as $file) {
  if (substr( $file, 0, 1 ) !== '.' && $file !== NULL) {
    $packages[] = $file;
  }
}

foreach ($packages as $package) {
  unset($yaml[$package]);

  $deps = file_get_contents('../vendor/gutenberg/' . $package . '/index.deps.json');
  if (!empty($deps)) {
    $deps = json_decode($deps);
  }

  $jsFiles = scanDir::scan('../vendor/gutenberg/' . $package, 'js');
  $cssFiles = scanDir::scan('../vendor/gutenberg/' . $package, 'css');

  $yaml[$package] = [];
  $yaml[$package]['js'] = [];
  foreach ($jsFiles as $file) {
    $yaml[$package]['js'][$file] = [];
  }

  $yaml[$package]['css'] = ['theme' => []];
  foreach ($cssFiles as $file) {
    if (!strpos($file, '-rtl')) {
      $yaml[$package]['css']['theme'][$file] = [];
    }
  }

  foreach ($deps as $dep) {
    $dep = str_replace('wp-', '', $dep);
    $yaml[$package]['dependencies'][] = 'gutenberg/' . $dep;
  }
}

file_put_contents('../gutenberg.libraries.yml', Yaml::dump($yaml, 4, 2, false, true));
