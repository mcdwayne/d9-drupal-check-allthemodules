<?php

namespace Drupal\search_file_attachments;

/**
 * Service class to define and check the java binary for the tika library.
 */
class JavaService {
  protected $javaPath;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->setJavaPath('java');
  }

  /**
   * Returns the java path.
   *
   * @return string
   *   The java path.
   */
  public function getJavaPath() {
    return $this->javaPath;
  }

  /**
   * Set the java path.
   *
   * @param string $path
   *   The path to the java binary.
   *
   * @return \Drupal\search_file_attachments\JavaService
   *   This class itself.
   */
  public function setJavaPath($path) {
    if (strpos(ini_get('extension_dir'), 'MAMP/')) {
      $path = 'export DYLD_LIBRARY_PATH=""; ' . $path;
    }

    $this->javaPath = $path;
    return $this;
  }

  /**
   * Checks the java path that the java binary can be executed.
   *
   * @return bool
   *   TRUE or FALSE if the java binary can be executed.
   */
  public function checkJava() {
    $path = $this->getJavaPath();

    $temp = tempnam(file_directory_temp(), 'asa');
    exec($path . ' -version > ' . $temp . ' 2>&1');
    $stderror = file_get_contents($temp);
    $found = preg_match('/Runtime Environment/', $stderror);

    return $found ? TRUE : FALSE;
  }

}
