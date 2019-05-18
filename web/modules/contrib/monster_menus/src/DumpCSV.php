<?php

/**
 * @file
 * Print a CSV dump of the entire MM tree.
 */

namespace Drupal\monster_menus;

use Drupal\monster_menus\GetTreeIterator\CSVDumpIter;

class DumpCSV {

  function dump($start = 1) {
    if (PHP_SAPI !== 'cli') {
      $GLOBALS['devel_shutdown'] = TRUE;    // prevent the devel module from outputting
      header('Content-type: text/plain');
    }
    $params = array(
      Constants::MM_GET_TREE_ITERATOR => new CSVDumpIter(),
      Constants::MM_GET_TREE_RETURN_BLOCK => TRUE,
      Constants::MM_GET_TREE_RETURN_FLAGS => TRUE,
    );
    mm_content_get_tree($start, $params);
    if (PHP_SAPI !== 'cli') {
      exit();
    }
  }

}
