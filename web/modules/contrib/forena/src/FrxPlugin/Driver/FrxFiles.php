<?php
// $Id$
/**
 * @file
 * File class for loading raw xml files as Sample data
 * This class is used as a reference implementation of a data engine, but is
 * also used to provide sample data files and reports.
 * @author metzlerd
 *
 */

namespace Drupal\forena\FrxPlugin\Driver;

use Drupal\forena\File\DataFileSystem;

/**
 * Class FrxFiles
 * @FrxDriver(
 *   id="FrxFiles",
 *   name="XML File Driver"
 * )
 */
class FrxFiles extends DriverBase {
  private $path;
  public function __construct($name, $conf, DataFileSystem $fileSystem) {
    parent::__construct($name, $conf, $fileSystem);
    $this->comment_prefix = '<!--';
    $this->comment_suffix = '-->';
    $this->block_ext = 'xml';
    $path = '';
    if (isset($conf['uri'])) {
      list($protocol, $path) = explode('://', $conf['uri'], 2);
      if (!$path) $path = $protocol;
    }
    $this->path = $path;
  }

  public function getName() {
    return 'XML File';
  }


}