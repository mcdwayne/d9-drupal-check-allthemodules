<?php
/**
 * @file
 * Contains \Drupal\md_fontello\Archiver\MDZip.
 */

namespace Drupal\md_fontello\Archiver;

use Drupal\Core\Archiver\Zip;

class MDZip extends Zip {

  /**
   * @param $file
   * @return string
   */
  public function getContent($file) {
    $content = $this->zip->getFromName($file);
    return $content;
  }

  /**
   * @param $name
   * @param $new_name
   */
  public function renameFile($name, $new_name) {
    $this->zip->renameName($name, $new_name);
  }

}
