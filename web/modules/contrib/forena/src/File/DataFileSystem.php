<?php
namespace Drupal\forena\File;
use Drupal\forena\AppService;
use Drupal\forena\DataManager;
class DataFileSystem extends FileSystemBase {

  public $source = '';
  public $dmSvc;
  /**
   * @param $source
   *   Machine name of Data Source
   * @param $path
   *   Path to sql files for reports.
   * @param DataManager $dataManager
   *   Data Manager object used to get data
   */
  public function __construct($source, $path, DataManager $dataManager) {
    parent::__construct();
    $this->cacheKey = $this->cacheKey . ':' . $source;
    // Load default directory from configuration.
    $this->dmSvc = $dataManager;
    $this->source = $source;
    $data_path = AppService::instance()->dataDirectory();
    $this->source = $source;
    $this->dir = rtrim($data_path, '/');
    $this->includes[] = $path;
  }


  /**
   * List all data blocks a sure has access to.
   * 
   * @return array
   *   Array of data blocks.
   */
  public function userBlocks($search = '*') {
    $blocks = array();
    $this->validateAllCache();
    $sql = $this->getCache('sql');
    $inc = $this->getCache('inc');
    $xml = $this->getCache('xml');
    $data = array_merge($xml, $sql, $inc);
    if ($data) foreach ($data as $base_name => $obj) {

  	  if ($search == '*' || drupal_match_path($base_name, $search)) {
        if ($obj->cache) {
    	    $r = $this->dmSvc->repository($obj->cache['provider']);
    	    if ($r && $r->access($obj->cache['access'])) {
            $blocks[$base_name] = $obj;
    	    }
        }
  	  }
    }
    uksort($blocks, '\Drupal\forena\File\DataFile::blockCompare');
    return $blocks;
  }


  /**
   * Sort compare function for sorting data by category then title.
   * @param string $a
   * @param string $b
   * @return number
   */
  static public function blockCompare($a, $b) {
    $c = strnatcasecmp($a, $b);
    return $c;
  }

  /**
   * @param $object
   * @return array
   * Extract data from an SQL file.
   */
  public function parseSQLFile($object) {
    $file = file_get_contents($object->file);
    $src = $this->dmSvc->parseSQL($file);
    $metaData = [
      'provider' => $this->source,
      'name' => $this->source . '/' . $this->base_name,
      'access' => @$src['access'],
      'options' => @$src['options'],
    ];
    return $metaData;
  }

  /**
   * Should load cache data based on that.
   * @see FrxFile::buildCache()
   */
  public function extractMetaData(&$object) {
    switch($object->ext) {
      case 'sql':
        $object->metaData = $this->parseSQLFile($object);
        break;
    }
  }


}
