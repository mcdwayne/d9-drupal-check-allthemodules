<?php

namespace Drupal\vde_drush\Plugin\FormatManipulator;

use Drupal\vde_drush\FormatManipulatorDefault;

/**
 * Implements xlsx format handler.
 *
 * @FormatManipulator(
 *   id="xlsx"
 * )
 */
class FormatManipulatorXlsx extends FormatManipulatorDefault {

  /**
   * {@inheritdoc}
   */
  protected function extractHeader(&$content) {
    /*
     * TODO: add better workaround for xls.
    $vdeFileRealPath = \Drupal::service('file_system')->realpath($context['sandbox']['vde_file']);
    $previousExcel = \PHPExcel_IOFactory::load($vdeFileRealPath);
    file_put_contents($vdeFileRealPath, $string);
    $currentExcel = \PHPExcel_IOFactory::load($vdeFileRealPath);

    // Append all rows to previous created excel.
    $rowIndex = $previousExcel->getActiveSheet()->getHighestRow();
    foreach ($currentExcel->getActiveSheet()->getRowIterator() as $row) {
      if ($row->getRowIndex() == 1) {
        // Skip header.
        continue;
      }
      $rowIndex++;
      $colIndex = 0;
      foreach ($row->getCellIterator() as $cell) {
        $previousExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex++, $rowIndex, $cell->getValue());
      }
    }

    $objWriter = new \PHPExcel_Writer_Excel2007($previousExcel);
    $objWriter->save($vdeFileRealPath);
    */

    throw new \Exception(dt('Xlsx format manipulation is not supported yet.'));
  }

}
