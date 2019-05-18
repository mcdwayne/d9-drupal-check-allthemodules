<?php

namespace Drupal\migrate_sql_subrow\Plugin\migrate\source;

use Drupal\Component\Utility\Html;

/**
 * Provides a almost complete migrate source to import multiple images.
 *
 * Still abstract as it will need to be subclassed and provided a bit of
 * information about the exact database it is importing from.
 */
abstract class SqlSubRowImageFromHtml extends SqlSubRowBase {

  /**
   * Get the column name to examine for sub rows.
   *
   * @return string
   *   The column name.
   */
  abstract protected function getMultiRowColumn();

  /**
   * {@inheritdoc}
   */
  protected function testMainRow(array $main_row) {
    return preg_match("/<img.*?/s", $main_row[$this->getMultiRowColumn()], $matches);
  }

  /**
   * {@inheritdoc}
   */
  protected function generateDependentRows(array $main_row) {
    $content = Html::load($main_row[$this->getMultiRowColumn()]);
    $files = [];
    foreach($content->getElementsByTagName('img') as $element) {
      // Require 'src' attribute.
      if (!$src = urldecode($element->getAttribute('src'))) {
        continue;
      }

      if (substr($src,0,1) == '/') {
        $src = '.' . $src;
      }
      $pathinfo = pathinfo($src);
      $files[] = [
        'filename' => $pathinfo['basename'],
        'path' => $pathinfo['dirname'],
        'pathname' => $src,
        'name' => $element->getAttribute('alt'),
      ] + $main_row;
    }
    return $files;
  }

}
