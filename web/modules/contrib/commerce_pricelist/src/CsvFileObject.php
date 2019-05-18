<?php

namespace Drupal\commerce_pricelist;

/**
 * Defines a wrapper around CSV data in a file.
 *
 * Extends SPLFileObject to:
 * - Skip header rows on rewind.
 * - Address columns by header name instead of numeric index.
 * - Support mapping header names to custom keys.
 */
class CsvFileObject extends \SplFileObject {

  /**
   * Whether the file has a header row.
   *
   * @var bool
   */
  protected $hasHeader = FALSE;

  /**
   * The human-readable column headers, keyed by column index in the CSV.
   *
   * @var string[]
   */
  protected $headerMapping = [];

  /**
   * The loaded header.
   *
   * @var string[]
   */
  protected $header = [];

  /**
   * Constructs a new CsvFileObject object.
   *
   * @param string $file_name
   *   The filename.
   * @param bool $has_header
   *   Whether the loaded file has a header row.
   * @param array $header_mapping
   *   The header mapping (real_column => mapped_column).
   * @param array $csv_options
   *   The CSV options (delimiter, enclosure, escape).
   */
  public function __construct($file_name, $has_header = FALSE, array $header_mapping = [], array $csv_options = []) {
    parent::__construct($file_name);

    $this->setFlags(self::READ_CSV | self::READ_AHEAD | self::DROP_NEW_LINE | self::SKIP_EMPTY);
    $options = array_merge([
      'delimiter' => ',',
      'enclosure' => '"',
      'escape' => '\\',
    ], $csv_options);
    $this->setCsvControl($options['delimiter'], $options['enclosure'], $options['escape']);

    $this->hasHeader = $has_header;
    $this->headerMapping = $header_mapping;
    if ($this->hasHeader) {
      $this->seek(0);
      $this->header = $this->current();
      $this->seek(1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $index = $this->hasHeader ? 1 : 0;
    $this->seek($index);
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    $row = parent::current();
    if (!$row) {
      // Invalid row, stop here.
      return $row;
    }
    if ($this->hasHeader && $this->key() === 0) {
      // Only data rows can be remapped.
      return $row;
    }

    $remapped_row = [];
    foreach ($row as $key => $value) {
      $new_key = $key;
      // Use the column name from the header as the default key.
      if ($this->hasHeader) {
        $new_key = trim($this->header[$key]);
      }
      // Map the selected key to the desired one, if any.
      if (isset($this->headerMapping[$new_key])) {
        $new_key = $this->headerMapping[$new_key];
      }
      $remapped_row[$new_key] = $value;
    }

    return $remapped_row;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $count = iterator_count($this);
    // The iterator_count() sets the pointer to the last element.
    $this->rewind();

    return $count;
  }

}
