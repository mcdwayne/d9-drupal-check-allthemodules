<?php
/**
 * @file FileSystemBase.inc
 * File toolbox for manipulating files
 * contained tn the report directory.
 */
namespace Drupal\forena\File;

abstract class FileSystemBase implements FileInterface {
  const CACHE_KEY='filesystem';

  // Location to write files to.
  public $write_dir;

  // Locations to search for files.
  public $includes = []; //Other places to look for a directory.

  // Index of fields organized by extension then base name
  public $type_index = [];

  // Index of fields organized by base name then extension.
  public $name_index = [];

  // Indicates whether the directory needs scanning.
  public $needScan = TRUE;

  // Indicates whether state needs saving
  public $needSave = FALSE;

  // Cache of all infomration about files.
  public $cache;

  // The base directory where reports can be written to.
  public $dir = '';

  // Indicates whether the cache has been validated once or not
  public $validated = FALSE;

  public $cacheKey;

  public function __construct() {
    $this->cacheKey = static::CACHE_KEY;
  }


  /**
   * Recursive function which scans the directory and loads the base indexes.
   * @param $directory
   *   Scans a directory ignoring hidden files.
   * @param $files
   */
  private function scanDirectory(&$directory, &$files) {
    // Scan the directory for files.
    $d = @dir($directory);
    if ($d) while (false !== ($file = $d->read())) {
      $file_path = rtrim($d->path, '/') . '/' . trim($file, '/');

      // Determine extension
      if (is_file($file_path)) {
        @list($base_file, $ext) = explode('.', $file, 2);
        $files[$ext][$file_path] = filemtime($file_path);
      }
      elseif (is_dir($file_path)) {
        if (strpos($file, '.')!==0) {
          $this->scanDirectory($file_path, $files);
        }
      }
    }
    if ($d) $d->close();
  }

  /**
   * Parse a drectory
   * @param string $directory
   */
  protected function scanInclude($directory) {
    $is_include = strpos($directory, $this->dir) !== 0;
    $files = array();
    $this->scanDirectory($directory, $files);
    foreach($files as $ext => $files_of_type ) {
      foreach($files_of_type as $file=>$mtime) {
        // Determine file name without extension.
        $base_name = substr($file, strlen($directory) + 1, -1 * (strlen($ext) + 1));
        // NO Cache entry exists.
        if (!isset($this->cache[$ext][$base_name])) {
          $obj = new \stdClass();
          $obj->file = $file;
          $obj->ext = $ext;
          $obj->base = $base_name;
          $obj->mtime = $mtime;
          $obj->include = $is_include;
          $obj->override = FALSE;
          $this->extractMetaData($obj);
          $this->cache[$ext][$base_name] = $obj;
        }
        else {
          // If its our first pass on this replace the entry
          $entry = $this->cache[$ext][$base_name];

          // Find out if we are replacing an include
          if (!$is_include && $entry->include) {
            $entry->file = $file;
            $entry->mtime = $mtime;
            $entry->include = $is_include;
            $entry->override = TRUE;
            $this->extractMetaData($entry);
            $this->needSave = TRUE;
          }

          // Find out it is the same file and it needs to be changed.
          if ($entry->file == $file && $entry->mtime != $mtime) {
            $entry->mtime = $mtime;
            $this->extractMetaData($entry);
            $this->needSave = TRUE;
          }

          // If its a different file make sure its override is set
          if ($entry->file != $file && !$entry->override) {
            $entry->override = TRUE;
            $this->needSave = TRUE;
          }

          unset($this->filesToDelete[$ext][$base_name]);
        }
      }
    }
  }

  private function setFilesToDelete() {
    $this->filesToDelete = array();
    //Quickly make a list of files in the cache right now.
    if ($this->cache) foreach ($this->cache as $ext => $files_of_type) {
      $this->filesToDelete[$ext] = array_fill_keys(array_keys($files_of_type), 1);
    }
  }

  private function deleteMissingEntries() {
    foreach ($this->filesToDelete as $ext => $files_of_type ) {
      foreach ($files_of_type as $base_name => $val) {
        unset($this->cache[$ext][$base_name]);
        $this->needSave = TRUE;
      }
    }
  }

  public function scan() {
    // Add the base report files.
    if ($this->needScan) {
      $this->scanInclude($this->dir);

      // Now add the module provided ones.
      if ($this->includes) foreach ($this->includes as $directory) {
        $this->scanInclude($directory);
      }
      if ($this->needSave) $this->setDirectoryState();
    }
  }

  public function getDirectoryState() {
    return \Drupal::state()->get($this->cacheKey);
  }

  public function setDirectoryState() {
    if ($this->cacheKey) return \Drupal::state()->set($this->cacheKey, $this->cache);
  }

  /**
   * Revert an individual report
   * @param $file
   * @return int 
   *   Number of files reverted. 
   */
  public function revert($file) {
    $i = 0;
    if ($this->includeExists($file)) {
      $file_to_delete = $this->dir . '/' . $file;
      if (file_exists($file_to_delete)) {
        if (is_writeable(dirname($file_to_delete))) {
          drupal_set_message(t('Removing customization %s', array('%s' => $file_to_delete)));
          unlink($file_to_delete);
          $i++;
        }
        else {
          drupal_set_message(t('Unable to revert %s', array('%s' => $file_to_delete)), 'error');
        }
      }
    }
    return $i;
  }

  /**
   * Determine if the file exists in the include path.
   * @param $file
   * @return bool 
   *   TRUE indicates that a base file exists on include path. 
   */
  public function includeExists($file) {
    $found = false;
    $i = 0;
    while(isset($this->includes[$i]) && !$found ) {
      $filename = $this->includes[$i] . '/' . $file;
      if (file_exists($this->includes[$i] . '/' . $file)) {
        $found = TRUE;
      }
      $i++;
    }
    return $found;
  }

  /**
   * Return the full path to the filename
   * 
   * @param $filename
   * @return string 
   *   Fully qualified fiel name. 
   */
  public function path($filename, $use_include = TRUE) {
    $path = $this->dir . '/' . $filename;
    if ($use_include && !file_exists($path)) {
      foreach ($this->includes as $dir) {
        if (file_exists($dir . '/' . $filename)) {
          $path = $dir . '/' . $filename;
        }
      }
    }
    return $path;
  }

  /**
   * Return the directory portion of a report filename.
   * @param string $filename
   *   relative path to file
   * @return string 
   *   Name of directory containing the file. 
   */
  public function directory($filename) {
    @list ($dir, $name_part) = explode('/', $filename, -1);
    return $this->dir . '/' . $dir;
  }

  /**
   * Return whether the file exists.
   * @param string $filename
   *   Relative path to file
   */
  public function exists($filename, $use_include = TRUE) {
    $path = $this->path($filename, $use_include);
    return file_exists($path);
  }

  /**
   * Return the contents of a file located in the report directory
   * @param string $filename
   *   filename and extension for report file.
   * @return 
   *   Contents of file 
   */
  public function contents($filename) {
    $path = $this->path($filename);
    if (file_exists($path)) {
      return file_get_contents($path);
    }
    else {
      return '';
    }
  }

  /**
   * Get all metadata for files of a specific extension.
   * @param $ext
   *   File extension being retrieved
   * @return array
   *   Array of metadata entries keyed by base filename.
   */
  protected function allMetadataForExt($ext) {
    if (isset($this->cache[$ext])) {
      return $this->cache[$ext];
    }
    else {
      return [];
    }
  }


  function verifyDirectory($fullpath, $recursive=FALSE) {
    static $path='';
    $success = TRUE;
    if (!$recursive) {
      $path = $this->dir;
      if (!is_writable($path)) {
        drupal_set_message(t('Directory %s is not modifiable', array('%s' => $path)), 'error');
        return FALSE;
      }
    }
    @list($dir, $file) = explode('/', $fullpath, 2);
    $path .= '/' . $dir;


    // Path
    if (!file_exists($path) && $file) {
      @mkdir($path);
      if (!is_writable($path)) {
        drupal_set_message(t('Error creating directory %path', array('%path' => $path)), 'error');
        return FALSE;
      }

    }
    // Recurse to next file.
    if ($file && strpos($file, '/')) {
      $this->verifyDirectory($file, TRUE);
    }
    return TRUE;
  }

  /**
   * Save a file into the report directory.
   * @param string $filename
   * @param string $data
   * @return void 
   */
  public function save($filename, $data) {
    $path = $this->dir . '/' . $filename;

    $this->verifyDirectory($filename);

    if (is_writable($path) || (!file_exists($path) && is_writable(dirname($path)))) {
      file_put_contents($path, $data);
    }
    else {
      Frx::error(t('Insufficient privileges to write file.'));
    }
  }

  /**
   * Delete a file from the directory.
   * @param string $filename
   * @return bool
   */
  public function delete($filename) {
    $path = $this->dir . '/' . $filename;
    $dir = getcwd();
    $do = TRUE;
    if (file_exists($path) && is_writeable($path) && is_writable(dirname($path))) {
      $info = pathinfo($path);
      chdir(dirname($path));
      $do = unlink($info['basename']);
      chdir($dir); ;
    }
    return $do;
  }

  /**
   * Retrieve path info
   * @param string $filename filename used for data
   * @param bool $use_include boolean value determining whether to search
   *   include path.
   * @return mixed
   */
  public function pathinfo($filename, $use_include = TRUE) {
    return pathinfo($this->path($filename, $use_include));
  }
  /**
   * Return an indicator as to whether the file is savable.
   * New files can be saved if the directory is writabel.
   * @param string $filename
   * @return bool
   */
  public function isWritable($filename) {
    return is_writeable($this->dir . "/$filename") || (!file_exists($this->dir . "/$filename"));
  }

  /**
   * Returns the cache entry based on a filename.
   * @param string $filename
   * @return object
   */
  public function getMetaData($filename) {
    list($base_name, $ext) = explode('.', $filename , 2);
    $cache = isset($this->cache[$ext][$base_name]) ? $this->cache[$ext][$base_name] : null ;
    return $cache;
  }


  /**
   * Test whether file is overriding code provided files.
   * @param $filename
   * @return mixed
   */
  public function isOverriden($filename) {
    $cache = $this->getMetaData($filename);
    return $cache->override;
  }

  /**
   * Determine whether the file is a cusomt implmentation.
   * @param $filename
   * @return bool
   */
  public function isCustom($filename) {
    $cache = $this->getMetaData($filename);
    return !$cache->include;
  }

}