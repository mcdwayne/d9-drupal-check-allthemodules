<?php

namespace Drupal\xhprof\XHProfLib\Storage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\xhprof\XHProfLib\Run;

class FileStorage implements StorageInterface {

  /**
   * @var string
   */
  private $dir;

  /**
   * @var string
   */
  private $suffix;

  /**
   * @param string $dir
   */
  public function __construct($dir = NULL) {
    if ($dir) {
      $this->dir = $dir;
    }
    else {
      $this->dir = ini_get("xhprof.output_dir") ?: sys_get_temp_dir();
    }
    $factory = \Drupal::configFactory();
    $extension = $factory->get('xhprof.config')->get('extension');
    if ($extension == 'uprofiler') {
      $this->suffix = 'uprofiler';
    }
    else {
      $this->suffix = 'xhprof';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'File Storage';
  }

  /**
   * {@inheritdoc}
   */
  public function getRun($run_id, $namespace) {
    $file_name = $this->fileName($run_id, $namespace);

    if (!file_exists($file_name)) {
      throw new \RuntimeException("Could not find file $file_name");
    }

    $serialized_contents = file_get_contents($file_name);
    $contents = @unserialize($serialized_contents);

    if ($contents === FALSE) {
      throw new \UnexpectedValueException("Unable to unserialize $file_name!");
    }

    $run = new Run($run_id, $namespace, $contents);
    return $run;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuns($namespace = NULL) {
    $files = $this->scanXHProfDir($this->dir, $namespace);
    $files = array_map(function ($f) {
      $f['date'] = strtotime($f['date']);
      return $f;
    }, $files);
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function saveRun($data, $namespace, $run_id) {
    // Use PHP serialize function to store the XHProf's
    // raw profiler data.
    $data = serialize($data);

    $file_name = $this->fileName($run_id, $namespace);
    $file = fopen($file_name, 'w');

    if ($file) {
      fwrite($file, $data);
      fclose($file);
    }
    else {
      throw new \Exception("Could not open $file_name\n");
    }

    return $run_id;
  }

  /**
   * @param string $dir
   * @param string $namespace
   *
   * @return array
   */
  private function scanXHProfDir($dir, $namespace = NULL) {
    $runs = array();
    if (is_dir($dir)) {
      foreach (glob("{$this->dir}/*.{$this->suffix}") as $file) {
        preg_match("/(?:(?<run>\w+)\.)(?:(?<namespace>[^.]+)\.)(?<ext>[\w.]+)/", basename($file), $matches);
        $runs[] = array(
          'run_id' => $matches['run'],
          'namespace' => $matches['namespace'],
          'basename' => htmlentities(basename($file)),
          'date' => date("Y-m-d H:i:s", filemtime($file)),
        );
      }
    }
    return array_reverse($runs);
  }

  /**
   * @param string $run_id
   * @param string $namespace
   *
   * @return string
   */
  private function fileName($run_id, $namespace) {
    $file = implode('.', array($run_id, $namespace, $this->suffix));

    if (!empty($this->dir)) {
      $file = $this->dir . "/" . $file;
    }
    return $file;
  }
}

