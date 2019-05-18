<?php

namespace Drupal\file_table_formatter;

class FileTableData {

  /**
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  protected $item;

  /**
   * @var string
   */
  protected $header;

  /**
   * @var array
   */
  protected $rows;

  /**
   * @var string
   */
  protected $title;

  /**
   * FileTableData constructor.
   * @param \Drupal\file\Entity\File $file
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem $item
   * @param string $header
   * @param array $rows
   * @param string $title
   */
  public function __construct(\Drupal\file\Entity\File $file, \Drupal\file\Plugin\Field\FieldType\FileItem $item = NULL, array $rows = NULL, $header = NULL, $title = NULL) {
    $this->file = $file;
    $this->item = $item;
    $this->header = $header;
    $this->rows = $rows;
    $this->title = $title;
  }

  /**
   * @return \Drupal\file\Entity\File
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  public function getItem() {
    return $this->item;
  }

  /**
   * @return string
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * @return array
   */
  public function getRows() {
    return $this->rows;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

}
