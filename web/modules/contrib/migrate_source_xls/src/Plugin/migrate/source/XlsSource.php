<?php
/**
 * @file
 * Contains migrate source plugin for XLS files.
 */

namespace Drupal\migrate_source_xls\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

/**
 * Source plugin for XLS files.
 *
 * @MigrateSource(
 *   id = "xls"
 * )
 */
class XlsSource extends SourcePluginBase {
  /**
   * PHPExcel file.
   *
   * @var \PHPExcel
   */
  protected $file;
  /**
   * File columns mapping.
   *
   * @var array
   */
  protected $columns = [];

  /**
   * XlsSource constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    // Path is required.
    if (empty($this->configuration['path'])) {
      throw new MigrateException('You must declare the "path" to the source xls file in your source settings.');
    }
    // Convert URI to realpath.
    $this->configuration['path'] = self::convertPath($this->configuration['path']);

    // Key field(s) are required.
    if (empty($this->configuration['keys'])) {
      throw new MigrateException('You must declare "keys" as a unique array of fields in your source settings.');
    }

    if (empty($this->configuration['header_row'])) {
      $this->configuration['header_row'] = 1;
    }

    // Columns are required.
    if (empty($this->configuration['columns'])) {
      throw new MigrateException('You must declare "columns" mapping.');
    }
    $this->columns = $this->configuration['columns'];
    // Prepare PHPExcel file.
    $this->file = \PHPExcel_IOFactory::createReaderForFile($this->configuration['path'])
      ->load($this->configuration['path']);
    $this->prepareColumns();
  }

  /**
   * Prepare columns.
   */
  private function prepareColumns() {
    $columns = [];
    $iterator = $this->file
      ->getActiveSheet()
      ->getRowIterator($this->configuration['header_row'], $this->configuration['header_row']);
    /** @var \PHPExcel_Cell $cell */
    foreach ($iterator->current()->getCellIterator() as $cell) {
      $header = rtrim($cell->getValue());
      if (!empty($header)) {
        foreach ($this->columns as $column) {
          if (!isset($column[$header])) {
            continue;
          }
          $columns[$cell->getColumn()] = $column[$header];
          break;
        }
      }
    }
    $this->columns = $columns;
  }

  /**
   * Convert URI path to realpath.
   *
   * @param string $uri
   *   File URI.
   *
   * @return string
   *   Realpath of the source file.
   */
  private static function convertPath($uri) {
    return \Drupal::service('file_system')->realpath($uri);
  }

  /**
   * Return a string representing the source query.
   *
   * @return string
   *   The file path.
   */
  public function __toString() {
    return $this->configuration['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $excel = $this->file;
    if (isset($this->configuration['sheet_name'])) {
      $iterator = $excel->setActiveSheetIndexByName($this->configuration['sheet_name']);
    }
    else {
      $iterator = $excel->setActiveSheetIndex(0);
    }
    return $iterator
      ->getRowIterator($this->configuration['header_row'] + 1, $this->count() + 1);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->currentSourceIds = NULL;
    $this->currentRow = NULL;
    // In order to find the next row we want to process, we ask the source
    // plugin for the next possible row.
    while (!isset($this->currentRow) && $this->getIterator()->valid()) {
      /** @var \PHPExcel_Worksheet_RowIterator $iterator */
      $row_data = []; $iterator = $this->getIterator();
      /** @var \PHPExcel_Cell $cell */
      foreach ($iterator->current()->getCellIterator() as $cell) {
        if (isset($this->columns[$cell->getColumn()])) {
          $column = $this->columns[$cell->getColumn()];
          $value = $cell->getValue();
          $row_data[$column] = $value;
        }
      }
      $row = new Row($row_data, $this->migration->getSourcePlugin()->getIds(), $this->migration->getDestinationIds());

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless getNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Clear any previous messages for this row before potentially adding
      // new ones.
      if (!empty($this->currentSourceIds)) {
        $this->idMap->delete($this->currentSourceIds, TRUE);
      }

      // Preparing the row gives source plugins the chance to skip.
      if ($this->prepareRow($row) === FALSE) {
        continue;
      }

      // Check whether the row needs processing.
      // 1. This row has not been imported yet.
      // 2. Explicitly set to update.
      // 3. The row is newer than the current highwater mark.
      // 4. If no such property exists then try by checking the hash of the row.
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        $this->currentRow = $row->freezeSource();
      }
      $iterator->next();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIDs() {
    $ids = [];
    foreach ($this->configuration['keys'] as $ind => $key) {
      $ids[$key]['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $excel = $this->file;
    if (isset($this->configuration['sheet_name'])) {
      $iterator = $excel->setActiveSheetIndexByName($this->configuration['sheet_name']);
    }
    else {
      $iterator = $excel->setActiveSheetIndex(0);
    }
    $count = (int) $iterator->getHighestDataRow() - 1;
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->columns;
  }
}
